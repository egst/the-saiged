<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use TheSaiged\Controllers\SearchController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Search\SearchResult;
use TheSaiged\Search\SearchService;
use TheSaiged\Tests\TestCase;

final class SearchControllerTest extends TestCase {

    function testSearchReturnsSerializedResults (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('search')
                    ->with('art')
                    ->willReturn([new SearchResult('about', 'About', 'A gallery of <mark>art</mark>.')])
        );

        $response = $this->invoke($this->request(['q' => 'art']));

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertSame([
            ['path' => 'about', 'title' => 'About', 'snippet' => 'A gallery of <mark>art</mark>.'],
        ], $body['results']);
    }

    function testMissingQueryStillReturnsEmptyResultsSuccessfully (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('search')
                    ->with('')
                    ->willReturn([])
        );

        $response = $this->invoke($this->request([]));

        $this->assertSame(200, $response->status);
        $this->assertSame(['results' => []], json_decode($response->body, true));
    }

    /** @param Closure(MockObject&SearchService) $configuration */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(SearchService::class);
        $configuration($service);
        Container::set(SearchService::class, $service);
    }

    /** @param array<string, string> $query */
    private function request (array $query): Request {
        return new Request(Method::GET, new Path('/api/search'), new Query($query));
    }

    private function invoke (Request $request): Response {
        return (SearchController::handler('search'))($request);
    }

}
