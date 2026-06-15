<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Uploads;

use Closure;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use TheSaiged\Core\Container;
use TheSaiged\Tests\TestCase;
use TheSaiged\Uploads\UnsupportedMediaTypeException;
use TheSaiged\Uploads\Upload;
use TheSaiged\Uploads\UploadId;
use TheSaiged\Uploads\UploadInput;
use TheSaiged\Uploads\UploadKind;
use TheSaiged\Uploads\UploadRepository;
use TheSaiged\Uploads\UploadService;
use TheSaiged\Uploads\UploadStorage;

/**
 * Unit tests for UploadService. UploadRepository + UploadStorage are
 * mocked; the service's job is the orchestration between them —
 * MIME → kind mapping, atomic create (insert + saveOriginal +
 * setDimensions, with rollback on disk failure), id-based delete with
 * disk cleanup.
 *
 * The actual DB chrono / Imagick / filesystem behavior is exercised by
 * the integration tests on UploadRepository + UploadStorage.
 */
final class UploadServiceTest extends TestCase {

    function testCreateRejectsUnsupportedMime (): void {
        $this->mock(
            repo:    fn ($repo)    => $repo->expects($this->never())->method('insert'),
            storage: fn ($storage) => $storage->expects($this->never())->method('saveOriginal'),
        );

        $this->expectException(UnsupportedMediaTypeException::class);
        Container::get(UploadService::class)->create(
            new UploadInput('/tmp/x', 'x.txt', 12, 'text/plain'),
        );
    }

    #[TestWith(['image/jpeg', 'image'])]
    #[TestWith(['image/png',  'image'])]
    #[TestWith(['image/gif',  'image'])]
    #[TestWith(['image/webp', 'image'])]
    #[TestWith(['video/mp4',  'video'])]
    #[TestWith(['video/webm', 'video'])]
    function testCreateMapsMimeToKind (string $mime, string $expectedKindValue): void {
        $upload = $this->fixtureUpload(mime: $mime, kind: UploadKind::from($expectedKindValue));
        $this->mock(
            repo:    function ($repo) use ($upload, $expectedKindValue) {
                $repo->expects($this->once())
                    ->method('insert')
                    ->with(
                        $this->anything(),
                        $this->anything(),
                        UploadKind::from($expectedKindValue),
                        $this->anything(),
                        null,
                        null,
                    )
                    ->willReturn(1);
                $repo->method('getById')->willReturn($upload);
            },
            storage: fn ($storage) =>
                $storage
                    ->method('saveOriginal')
                    ->willReturn([null, null])
        );

        Container::get(UploadService::class)->create(
            new UploadInput('/tmp/x', 'x', 1, $mime),
        );
    }

    function testCreatePersistsDimensionsWhenStorageReturnsThem (): void {
        $upload = $this->fixtureUpload();
        $this->mock(
            repo: function ($repo) use ($upload) {
                $repo->method('insert')->willReturn(7);
                $repo->method('getById')->willReturn($upload);
                $repo->expects($this->once())
                    ->method('setDimensions')
                    ->with(7, 800, 600);
            },
            storage: fn ($storage) =>
                $storage
                    ->method('saveOriginal')
                    ->willReturn([800, 600])
        );

        $result = Container::get(UploadService::class)->create(
            new UploadInput('/tmp/x', 'x.jpg', 1, 'image/jpeg'),
        );

        $this->assertSame($upload, $result);
    }

    function testCreateRollsBackOnStorageFailure (): void {
        $this->mock(
            repo: function ($repo) {
                $repo->method('insert')->willReturn(7);
                $repo->method('getById')->willReturn($this->fixtureUpload());
                // Both delete (DB row) and the matching storage->deleteAll
                // must be called when the disk write throws.
                $repo->expects($this->once())->method('delete')->with(7);
            },
            storage: function ($storage) {
                $storage->method('saveOriginal')->willThrowException(new RuntimeException('disk full'));
                $storage->expects($this->once())->method('deleteAll')->with(7);
            },
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('disk full');
        Container::get(UploadService::class)->create(
            new UploadInput('/tmp/x', 'x.jpg', 1, 'image/jpeg'),
        );
    }

    function testDeleteReturnsFalseForUnknownId (): void {
        $this->mock(
            repo:    fn ($repo) =>
                $repo
                    ->method('getById')
                    ->willReturn(null),
            storage: fn ($storage) =>
                $storage
                    ->expects($this->never())
                    ->method('deleteAll'),
        );

        $this->assertFalse(
            Container::get(UploadService::class)->delete(new UploadId(99)),
        );
    }

    function testDeleteRemovesDiskAndRowForExistingUpload (): void {
        $upload = $this->fixtureUpload();
        $this->mock(
            repo: function ($repo) use ($upload) {
                $repo->method('getById')->willReturn($upload);
                $repo->expects($this->once())->method('delete')->with(42);
            },
            storage: fn ($storage) =>
                $storage
                    ->expects($this->once())
                    ->method('deleteAll')
                    ->with(42),
        );

        $this->assertTrue(
            Container::get(UploadService::class)->delete(new UploadId(42)),
        );
    }

    function testEnsureVariantReturnsNullWhenUploadMissing (): void {
        $this->mock(
            repo:    fn ($repo) =>
                $repo
                    ->method('getById')
                    ->willReturn(null),
            storage: fn ($storage) =>
                $storage
                    ->expects($this->never())
                    ->method('ensureVariant'),
        );

        $this->assertNull(
            Container::get(UploadService::class)->ensureVariant(new UploadId(99), 1920, 1080),
        );
    }

    function testEnsureVariantReturnsNullWhenUploadIsVideo (): void {
        $video = $this->fixtureUpload(mime: 'video/mp4', kind: UploadKind::Video);
        $this->mock(
            repo:    fn ($repo) =>
                $repo
                    ->method('getById')
                    ->willReturn($video),
            storage: fn ($storage) =>
                $storage
                    ->expects($this->never())
                    ->method('ensureVariant'),
        );

        $this->assertNull(
            Container::get(UploadService::class)->ensureVariant(new UploadId(42), 1920, 1080),
        );
    }

    function testEnsureVariantDelegatesToStorageForImage (): void {
        $image = $this->fixtureUpload();
        $this->mock(
            repo: fn ($repo) =>
                $repo
                    ->method('getById')
                    ->willReturn($image),
            storage: fn ($storage) =>
                $storage
                    ->expects($this->once())
                    ->method('ensureVariant')
                    ->with($image, 1920, 1080)
                    ->willReturn('/uploads/42/1920x1080-cover.webp'),
        );

        $this->assertSame(
            '/uploads/42/1920x1080-cover.webp',
            Container::get(UploadService::class)->ensureVariant(new UploadId(42), 1920, 1080),
        );
    }

    function testGetUnwrapsUploadIdToInt (): void {
        $upload = $this->fixtureUpload();
        $this->mock(
            repo:    fn ($repo) =>
                $repo
                    ->expects($this->once())
                    ->method('getById')
                    ->with(42)
                    ->willReturn($upload),
            storage: fn ($storage) => null,
        );

        $this->assertSame(
            $upload,
            Container::get(UploadService::class)->get(new UploadId(42)),
        );
    }

    /**
     * @param Closure(MockObject&UploadRepository) $repo
     * @param Closure(MockObject&UploadStorage)    $storage
     */
    private function mock (Closure $repo, Closure $storage): void {
        $repoMock    = $this->createMock(UploadRepository::class);
        $storageMock = $this->createMock(UploadStorage::class);
        $repo   ($repoMock);
        $storage($storageMock);
        Container::set(UploadRepository::class, $repoMock);
        Container::set(UploadStorage::class,    $storageMock);
    }

    private function fixtureUpload (
        string     $mime = 'image/jpeg',
        UploadKind $kind = UploadKind::Image,
    ): Upload {
        return new Upload(
            id:         42,
            filename:   'photo.jpg',
            mime:       $mime,
            kind:       $kind,
            size:       1234,
            width:      800,
            height:     600,
            uploadedAt: '2026-06-16 12:00:00',
        );
    }

}
