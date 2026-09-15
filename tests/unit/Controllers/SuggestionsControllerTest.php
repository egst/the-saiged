<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use TheSaiged\Controllers\SuggestionsController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Search\SuggestionsService;
use TheSaiged\Tests\TestCase;

final class SuggestionsControllerTest extends TestCase {

    function testGetReturnsServiceList (): void {
        $this->mockService(fn ($service) => $service->method('list')->willReturn(['a', 'b']));

        $response = $this->invoke('get', new Request(Method::GET, new Path('/api/search/suggestions'), new Query()));

        $this->assertSame(200, $response->status);
        $this->assertSame(['suggestions' => ['a', 'b']], json_decode($response->body, true));
    }

    function testPutSavesAndReturnsOk (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('save')
                    ->with(['find an artist'])
        );

        $response = $this->invoke('put', $this->request(['suggestions' => ['find an artist']]));

        $this->assertSame(200, $response->status);
        $this->assertTrue(json_decode($response->body, true)['ok']);
    }

    function testPutWithoutBodyReturns400 (): void {
        $response = $this->invoke('put', new Request(Method::PUT, new Path('/api/admin/search/suggestions'), new Query()));

        $this->assertSame(400, $response->status);
    }

    function testPutWithNonListSuggestionsReturns400 (): void {
        $response = $this->invoke('put', $this->request(['suggestions' => 'not a list']));

        $this->assertSame(400, $response->status);
    }

    function testPutWithNonStringEntryReturns400 (): void {
        $response = $this->invoke('put', $this->request(['suggestions' => ['ok', 5]]));

        $this->assertSame(400, $response->status);
    }

    function testPutPropagatesInvalidDataAs400 (): void {
        $this->mockService(
            fn ($service) =>
                $service->method('save')->willThrowException(new InvalidDataException('search suggestions', 'entries cannot be empty'))
        );

        $response = $this->invoke('put', $this->request(['suggestions' => ['']]));

        $this->assertSame(400, $response->status);
    }

    /** @param Closure(MockObject&SuggestionsService) $configuration */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(SuggestionsService::class);
        $configuration($service);
        Container::set(SuggestionsService::class, $service);
    }

    /** @param array<string, mixed> $body */
    private function request (array $body): Request {
        return new Request(Method::PUT, new Path('/api/admin/search/suggestions'), new Query(), body: $body);
    }

    private function invoke (string $method, Request $request): Response {
        return (SuggestionsController::handler($method))($request);
    }

}
