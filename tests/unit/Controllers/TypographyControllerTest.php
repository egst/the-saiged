<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use TheSaiged\Controllers\TypographyController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Tests\TestCase;
use TheSaiged\Typography\FontRole;
use TheSaiged\Typography\FontStyle;
use TheSaiged\Typography\TypographyService;
use TheSaiged\Uploads\UploadId;

/**
 * Unit tests for TypographyController — wiring layer only. TypographyService
 * is mocked; validation/resolution behaviour is covered by TypographyServiceTest.
 */
final class TypographyControllerTest extends TestCase {

    function testGetReturnsServicePayload (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('get')
                    ->willReturn(['heading' => [], 'text' => []])
        );

        $response = $this->invoke('get', new Request(Method::GET, new Path('/api/admin/typography'), new Query()));

        $this->assertSame(200, $response->status);
        $this->assertSame(['heading' => [], 'text' => []], json_decode($response->body, true));
    }

    function testAddFaceParsesBodyAndReturns201 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('addFace')
                    ->with(
                        FontRole::Text,
                        $this->callback(fn (UploadId $id) => $id->value === 9),
                        400,
                        700,
                        FontStyle::Italic,
                    )
                    ->willReturn(['id' => 1, 'weightMin' => 400, 'weightMax' => 700, 'style' => 'italic', 'upload' => null])
        );

        $response = $this->invoke('addFace', $this->addFaceRequest('text', [
            'uploadId'  => 9,
            'weightMin' => 400,
            'weightMax' => 700,
            'style'     => 'italic',
        ]));

        $this->assertSame(201, $response->status);
        $body = json_decode($response->body, true);
        $this->assertTrue($body['ok']);
        $this->assertSame(1, $body['face']['id']);
    }

    function testAddFaceUnknownRoleReturns400 (): void {
        $this->mockService(fn ($service) => $service->expects($this->never())->method('addFace'));

        $response = $this->invoke('addFace', $this->addFaceRequest('sidebar', [
            'uploadId' => 9, 'weightMin' => 400, 'weightMax' => 400, 'style' => 'normal',
        ]));

        $this->assertSame(400, $response->status);
    }

    function testAddFaceMissingFieldsReturns400 (): void {
        $this->mockService(fn ($service) => $service->expects($this->never())->method('addFace'));

        $response = $this->invoke('addFace', $this->addFaceRequest('text', ['uploadId' => 9]));

        $this->assertSame(400, $response->status);
    }

    function testAddFacePropagatesInvalidDataAs400 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('addFace')
                    ->willThrowException(new InvalidDataException('typography face', 'upload is not a font'))
        );

        $response = $this->invoke('addFace', $this->addFaceRequest('text', [
            'uploadId' => 9, 'weightMin' => 400, 'weightMax' => 400, 'style' => 'normal',
        ]));

        $this->assertSame(400, $response->status);
    }

    function testRemoveFaceReturns200WhenFound (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('removeFace')
                    ->with(5)
                    ->willReturn(true)
        );

        $response = $this->invoke('removeFace', $this->removeFaceRequest('5'));

        $this->assertSame(200, $response->status);
    }

    function testRemoveFaceReturns404WhenMissing (): void {
        $this->mockService(fn ($service) => $service->method('removeFace')->willReturn(false));

        $response = $this->invoke('removeFace', $this->removeFaceRequest('999'));

        $this->assertSame(404, $response->status);
    }

    /** @param array<string, mixed> $body */
    private function addFaceRequest (string $role, array $body): Request {
        $path = new Path("/api/admin/typography/$role/faces", ['role' => $role]);
        return new Request(Method::POST, $path, new Query(), [], $body);
    }

    private function removeFaceRequest (string $idParam): Request {
        $path = new Path("/api/admin/typography/faces/$idParam", ['id' => $idParam]);
        return new Request(Method::DELETE, $path, new Query(), [], null);
    }

    /** @param Closure(MockObject&TypographyService) $configuration */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(TypographyService::class);
        $configuration($service);
        Container::set(TypographyService::class, $service);
    }

    private function invoke (string $method, Request $request): Response {
        return (TypographyController::handler($method))($request);
    }

}
