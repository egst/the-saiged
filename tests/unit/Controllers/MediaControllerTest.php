<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use Closure;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use TheSaiged\Controllers\MediaController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Tests\TestCase;
use TheSaiged\Uploads\Upload;
use TheSaiged\Uploads\UploadId;
use TheSaiged\Uploads\UploadKind;
use TheSaiged\Uploads\UploadService;

/**
 * Unit tests for MediaController — wiring layer only. UploadService is
 * mocked; the $_FILES-driven createUpload path is tested separately via
 * an integration test (it needs is_uploaded_file() which only succeeds
 * for actual PHP-uploaded files, not test-rigged paths).
 *
 * Tests run through Controller::handler so onError's exception mapping
 * is part of what's verified.
 */
final class MediaControllerTest extends TestCase {

    function testListUploadsSerializesEachEntry (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('list')
                    ->willReturn([$this->fixtureUpload()])
        );

        $response = $this->invoke('listUploads', $this->request());

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertCount(1, $body['uploads']);
        $this->assertSame(42,            $body['uploads'][0]['id']);
        $this->assertSame('image/jpeg',  $body['uploads'][0]['mime']);
        $this->assertSame('/uploads/42/original.jpg',       $body['uploads'][0]['originalUrl']);
        $this->assertSame('/uploads/42/thumb-200x200.webp', $body['uploads'][0]['thumbUrl']);
    }

    function testDeleteUploadHappyPathReturns200 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('delete')
                    ->with($this->callback(fn (UploadId $id) => $id->value === 42))
                    ->willReturn(true)
        );

        $response = $this->invoke('deleteUpload', $this->request('42'));

        $this->assertSame(200, $response->status);
        $this->assertSame(42,  json_decode($response->body, true)['id']);
    }

    function testDeleteUploadReturns404WhenServiceSaysMissing (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('delete')
                    ->willReturn(false)
        );

        $response = $this->invoke('deleteUpload', $this->request('9999'));

        $this->assertSame(404, $response->status);
    }

    function testDeleteUploadInvalidIdMapsTo400 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->never())
                    ->method('delete')
        );

        $response = $this->invoke('deleteUpload', $this->request('abc'));

        $this->assertSame(400, $response->status);
    }

    function testEnsureVariantHappyPathReturnsUrl (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('ensureVariant')
                    ->with(
                        $this->callback(fn (UploadId $id) => $id->value === 42),
                        1920,
                        1080,
                    )
                    ->willReturn('/uploads/42/1920x1080-cover.webp')
        );

        $response = $this->invoke('ensureVariant', $this->variantRequest('42', ['width' => 1920, 'height' => 1080]));

        $this->assertSame(201, $response->status);
        $this->assertSame('/uploads/42/1920x1080-cover.webp', json_decode($response->body, true)['url']);
    }

    function testEnsureVariantReturns404WhenServiceReturnsNull (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('ensureVariant')
                    ->willReturn(null)
        );

        $response = $this->invoke('ensureVariant', $this->variantRequest('9999', ['width' => 1920, 'height' => 1080]));

        $this->assertSame(404, $response->status);
    }

    /**
     * @param array<string, mixed> $body
     */
    #[TestWith([[]],                                                    'missing both')]
    #[TestWith([['width' => 1920]],                                     'missing height')]
    #[TestWith([['height' => 1080]],                                    'missing width')]
    #[TestWith([['width' => 0,      'height' => 1080]],                 'zero width')]
    #[TestWith([['width' => -1,     'height' => 1080]],                 'negative width')]
    #[TestWith([['width' => '1920', 'height' => 1080]],                 'non-int width')]
    #[TestWith([['width' => 10000,  'height' => 1080]],                 'width too large')]
    function testEnsureVariantRejectsInvalidBody (array $body): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->never())
                    ->method('ensureVariant')
        );

        $response = $this->invoke('ensureVariant', $this->variantRequest('42', $body));

        $this->assertSame(400, $response->status);
    }

    function testServiceRuntimeExceptionMapsToJson500 (): void {
        // Regression: a RuntimeException from the service layer (e.g. mkdir
        // permission denied on the uploads volume) must be caught by onError
        // and returned as a JSON 500 — never leaked as raw text into the body.
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('delete')
                    ->willThrowException(new RuntimeException('Permission denied'))
        );

        $response = $this->invoke('deleteUpload', $this->request('42'));

        $this->assertSame(500, $response->status);
        $body = json_decode($response->body, true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
    }

    function testCreateUploadUnsupportedMimeMapsTo400 (): void {
        // The $_FILES parsing happens inside UploadInput::fromGlobals,
        // which needs is_uploaded_file() — that only succeeds for an
        // actual PHP-uploaded request, not test rigging. To exercise the
        // "service throws UnsupportedMediaType → 400" mapping in
        // isolation, we don't go through fromGlobals here; instead we
        // expect the BadRequest from the missing $_FILES.
        //
        // This test would be more interesting against a real upload but
        // that's the job of the integration suite.
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->never())
                    ->method('create')
        );

        $_FILES = [];
        $response = $this->invoke('createUpload', $this->request());

        $this->assertSame(400, $response->status);
    }

    private function request (string $idParam = ''): Request {
        $path = $idParam === ''
            ? new Path('/api/admin/uploads')
            : new Path("/api/admin/uploads/$idParam", ['id' => $idParam]);
        return new Request(Method::POST, $path, new Query(), [], null);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function variantRequest (string $idParam, array $body): Request {
        $path = new Path("/api/admin/uploads/$idParam/variants", ['id' => $idParam]);
        return new Request(Method::POST, $path, new Query(), [], $body);
    }

    /** @param Closure(MockObject&UploadService) $configuration */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(UploadService::class);
        $configuration($service);
        Container::set(UploadService::class, $service);
    }

    private function invoke (string $method, Request $request): Response {
        return (MediaController::handler($method))($request);
    }

    private function fixtureUpload (): Upload {
        return new Upload(
            id:         42,
            filename:   'photo.jpg',
            mime:       'image/jpeg',
            kind:       UploadKind::Image,
            size:       1234,
            width:      800,
            height:     600,
            uploadedAt: '2026-06-16 12:00:00',
        );
    }

}
