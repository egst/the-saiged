<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Typography;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\Container;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Tests\TestCase;
use TheSaiged\Typography\FontRole;
use TheSaiged\Typography\FontStyle;
use TheSaiged\Typography\TypographyFace;
use TheSaiged\Typography\TypographyRepository;
use TheSaiged\Typography\TypographyService;
use TheSaiged\Uploads\Upload;
use TheSaiged\Uploads\UploadId;
use TheSaiged\Uploads\UploadKind;
use TheSaiged\Uploads\UploadService;

final class TypographyServiceTest extends TestCase {

    function testGetReturnsEmptyListsWhenNoFacesSaved (): void {
        $repo = $this->createMock(TypographyRepository::class);
        $repo->method('listFaces')->willReturn([]);
        Container::set(TypographyRepository::class, $repo);
        Container::set(UploadService::class, $this->createMock(UploadService::class));

        $result = Container::get(TypographyService::class)->get();

        $this->assertSame(['heading' => [], 'text' => []], $result);
    }

    function testGetResolvesFacesWithTheirUpload (): void {
        $face   = $this->fixtureFace();
        $upload = $this->fixtureUpload();

        $repo = $this->createMock(TypographyRepository::class);
        $repo->method('listFaces')->willReturnMap([
            [FontRole::Heading, []],
            [FontRole::Text,    [$face]],
        ]);
        Container::set(TypographyRepository::class, $repo);

        $uploads = $this->createMock(UploadService::class);
        $uploads->method('get')->with($this->callback(fn (UploadId $id) => $id->value === 9))->willReturn($upload);
        Container::set(UploadService::class, $uploads);

        $result = Container::get(TypographyService::class)->get();

        $this->assertSame([], $result['heading']);
        $this->assertCount(1, $result['text']);
        $this->assertSame(400, $result['text'][0]['weightMin']);
        $this->assertSame(700, $result['text'][0]['weightMax']);
        $this->assertSame('normal', $result['text'][0]['style']);
        $this->assertSame('brand.otf', $result['text'][0]['upload']['filename']);
    }

    function testAddFaceThrowsWhenUploadUnknown (): void {
        Container::set(TypographyRepository::class, $this->createMock(TypographyRepository::class));
        $uploads = $this->createMock(UploadService::class);
        $uploads->method('get')->willReturn(null);
        Container::set(UploadService::class, $uploads);

        $this->expectException(InvalidDataException::class);
        Container::get(TypographyService::class)->addFace(FontRole::Text, new UploadId(99), 400, 400, FontStyle::Normal);
    }

    function testAddFaceThrowsWhenUploadIsNotFont (): void {
        Container::set(TypographyRepository::class, $this->createMock(TypographyRepository::class));
        $uploads = $this->createMock(UploadService::class);
        $uploads->method('get')->willReturn($this->fixtureUpload(kind: UploadKind::Image));
        Container::set(UploadService::class, $uploads);

        $this->expectException(InvalidDataException::class);
        Container::get(TypographyService::class)->addFace(FontRole::Text, new UploadId(9), 400, 400, FontStyle::Normal);
    }

    #[TestWith([0, 400])]
    #[TestWith([400, 1001])]
    #[TestWith([700, 400])]
    function testAddFaceThrowsOnInvalidWeightRange (int $min, int $max): void {
        Container::set(TypographyRepository::class, $this->createMock(TypographyRepository::class));
        $uploads = $this->createMock(UploadService::class);
        $uploads->method('get')->willReturn($this->fixtureUpload());
        Container::set(UploadService::class, $uploads);

        $this->expectException(InvalidDataException::class);
        Container::get(TypographyService::class)->addFace(FontRole::Text, new UploadId(9), $min, $max, FontStyle::Normal);
    }

    function testAddFacePersistsAndReturnsSerializedFace (): void {
        $upload = $this->fixtureUpload();
        $face   = $this->fixtureFace();

        $repo = $this->createMock(TypographyRepository::class);
        $repo->expects($this->once())
            ->method('addFace')
            ->with(FontRole::Text, 9, 400, 700, FontStyle::Normal)
            ->willReturn(1);
        $repo->method('getFace')->with(1)->willReturn($face);
        Container::set(TypographyRepository::class, $repo);

        $uploads = $this->createMock(UploadService::class);
        $uploads->method('get')->willReturn($upload);
        Container::set(UploadService::class, $uploads);

        $result = Container::get(TypographyService::class)->addFace(FontRole::Text, new UploadId(9), 400, 700, FontStyle::Normal);

        $this->assertSame(1,   $result['id']);
        $this->assertSame(400, $result['weightMin']);
    }

    function testRemoveFaceDelegatesToRepository (): void {
        $repo = $this->createMock(TypographyRepository::class);
        $repo->expects($this->once())->method('removeFace')->with(5)->willReturn(true);
        Container::set(TypographyRepository::class, $repo);
        Container::set(UploadService::class, $this->createMock(UploadService::class));

        $this->assertTrue(Container::get(TypographyService::class)->removeFace(5));
    }

    private function fixtureFace (): TypographyFace {
        return new TypographyFace(
            id:        1,
            role:      FontRole::Text,
            uploadId:  9,
            weightMin: 400,
            weightMax: 700,
            style:     FontStyle::Normal,
        );
    }

    private function fixtureUpload (UploadKind $kind = UploadKind::Font): Upload {
        return new Upload(
            id:         9,
            filename:   'brand.otf',
            mime:       'application/vnd.ms-opentype',
            kind:       $kind,
            size:       5000,
            width:      null,
            height:     null,
            uploadedAt: '2026-06-16 12:00:00',
        );
    }

}
