<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Throwable;
use TheSaiged\Controllers\PublicController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Exception\HttpException;
use TheSaiged\Core\Http\Exception\NotFoundException;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Pages\Page;
use TheSaiged\Pages\PageService;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Tests\TestCase;

/**
 * Unit tests for PublicController — wiring + HTML response shaping.
 * PageService is mocked. The "what counts as public" rule lives in
 * PageService::findPublishedByPath and is tested in PageServiceTest;
 * here we only verify the controller dispatches correctly and renders
 * 200 / 404 / 500 with the right body content.
 */
final class PublicControllerTest extends TestCase {

    function testPagePassesPathToServiceAndReturns200Render (): void {
        $page = new Page(1, 'about', 'About', null, PageStatus::Published, []);
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('findPublishedByPath')
                    ->with('about')
                    ->willReturn($page)
        );

        $response = $this->invoke('page', $this->request('/about'));

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('<title>About</title>', $response->body);
    }

    function testPageStripsLeadingSlashBeforeLookup (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('findPublishedByPath')
                    ->with('work/foo')
                    ->willReturn(null)
        );

        $this->invoke('page', $this->request('/work/foo'));
    }

    #[TestWith(['/nope'])]
    #[TestWith(['/'])]
    #[TestWith(['/missing/nested'])]
    function testPageReturns404WhenServiceReturnsNull (string $path): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('findPublishedByPath')
                    ->willReturn(null)
        );

        $response = $this->invoke('page', $this->request($path));

        $this->assertSame(404, $response->status);
        $this->assertStringContainsString('Page not found', $response->body);
    }

    function testNotFoundDirectCallReturnsHtml (): void {
        $this->mockService(fn ($service) => null);

        $response = (new PublicController($this->createMock(PageService::class)))
            ->notFound($this->request('/anything'));

        $this->assertSame(404, $response->status);
        $this->assertStringContainsString('Page not found', $response->body);
    }

    /**
     * @param  Throwable $thrown               passed to onError
     * @param  int       $expectedStatus       expected response status
     * @param  string    $expectedBodySubstr   substring expected in body
     */
    #[DataProvider('onErrorCases')]
    function testOnErrorMapsExceptionToHtmlResponse (Throwable $thrown, int $expectedStatus, string $expectedBodySubstr): void {
        $controller = new PublicController($this->createMock(PageService::class));

        $response = $controller->onError($thrown, $this->request('/anything'));

        $this->assertSame($expectedStatus, $response->status);
        $this->assertStringContainsString($expectedBodySubstr, $response->body);
    }

    /** @return iterable<string, array{Throwable, int, string}> */
    public static function onErrorCases (): iterable {
        yield 'NotFoundException → not-found page'   => [new NotFoundException(),          404, 'Page not found'];
        yield 'non-404 HttpException → generic page' => [new HttpException(500, 'kaboom'), 500, 'Something went wrong'];
        yield 'plain throwable → generic page'       => [new RuntimeException('boom'),     500, 'Something went wrong'];
    }

    function testOnErrorDoesNotLeakInternalMessage (): void {
        $controller = new PublicController($this->createMock(PageService::class));

        $response = $controller->onError(new RuntimeException('boom-secret'), $this->request('/'));

        $this->assertStringNotContainsString('boom-secret', $response->body,
            'Internal messages must not surface in the public error page');
    }

    function testStaticErrorPageReturnsHtmlWithoutDependencies (): void {
        // errorPage is the last-resort renderer used by Entry::hardFail
        // when no controller can be safely constructed — it must work
        // standalone, without Container or any dependency.
        $html = PublicController::errorPage();

        $this->assertStringContainsString('<!DOCTYPE html>',      $html);
        $this->assertStringContainsString('Something went wrong', $html);
    }

    private function request (string $path): Request {
        return new Request(Method::GET, new Path($path), new Query());
    }

    /** @param Closure(MockObject&PageService) $configuration */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(PageService::class);
        $configuration($service);
        Container::set(PageService::class, $service);
    }

    private function invoke (string $method, Request $request): Response {
        return (PublicController::handler($method))($request);
    }

}
