<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use TheSaiged\Controllers\ShellController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Header\HeaderShell;
use TheSaiged\Shell\ShellService;
use TheSaiged\Tests\TestCase;

/**
 * Unit tests for ShellController — wiring layer only. ShellService is
 * mocked; validation/default() behaviour is covered by ShellServiceTest
 * and the individual shell VO tests.
 */
final class ShellControllerTest extends TestCase {

    function testGetReturnsTypeAndSerializedData (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('get')
                    ->with('header')
                    ->willReturn(HeaderShell::default())
        );

        $response = $this->invoke('get', $this->request('header'));

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertSame('header', $body['type']);
        $this->assertSame(['links' => [], 'logoUploadId' => null], $body['data']);
    }

    function testGetPropagatesUnknownTypeAs400 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('get')
                    ->willThrowException(new InvalidDataException('shell type', 'unknown type: sidebar'))
        );

        $response = $this->invoke('get', $this->request('sidebar'));

        $this->assertSame(400, $response->status);
    }

    function testPutSavesBodyAndReturnsOk (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('save')
                    ->with('header', ['links' => [], 'logoUploadId' => null])
        );

        $response = $this->invoke('put', $this->request('header', ['links' => [], 'logoUploadId' => null]));

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertTrue($body['ok']);
        $this->assertSame('header', $body['type']);
    }

    function testPutWithoutBodyReturns400 (): void {
        $response = $this->invoke('put', new Request(Method::PUT, new Path('/api/admin/shell/header', ['type' => 'header']), new Query()));

        $this->assertSame(400, $response->status);
    }

    function testPutPropagatesInvalidShapeAs400 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('save')
                    ->willThrowException(new InvalidDataException('header data', 'links must be a list'))
        );

        $response = $this->invoke('put', $this->request('header', ['links' => 'nope']));

        $this->assertSame(400, $response->status);
    }

    /** @param array<string, mixed> $body */
    private function request (string $type, ?array $body = null): Request {
        $path = new Path("/api/admin/shell/$type", ['type' => $type]);
        return new Request(
            $body === null ? Method::GET : Method::PUT,
            $path,
            new Query(),
            [],
            $body,
        );
    }

    /** @param Closure(MockObject&ShellService) $configuration */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(ShellService::class);
        $configuration($service);
        Container::set(ShellService::class, $service);
    }

    private function invoke (string $method, Request $request): Response {
        return (ShellController::handler($method))($request);
    }

}
