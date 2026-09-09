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
use TheSaiged\Shell\Layout;
use TheSaiged\Tests\TestCase;

/**
 * Unit tests for PublicController — wiring + HTML response shaping.
 * PageService and Layout are mocked. The "what counts as public" rule
 * lives in PageService::findPublishedByPath (tested in PageServiceTest)
 * and full-document assembly lives in Layout (tested in LayoutTest) —
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
        $this->mockLayout(fn ($layout) => $layout->method('render')->with($page)->willReturn('<title>About</title>'));

        $response = $this->invoke('page', $this->request('/about'));

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('<title>About</title>', $response->body);
    }

    function testPageWithXPartialHeaderReturnsJsonPartialInsteadOfFullDocument (): void {
        $page = new Page(1, 'about', 'About', null, PageStatus::Published, []);
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('findPublishedByPath')
                    ->willReturn($page)
        );

        $response = $this->invoke('page', $this->request('/about', ['x-partial' => '1']));

        $this->assertSame(200, $response->status);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $response->body);
        $this->assertStringContainsString('"title":"About"',   $response->body);
    }

    function testPageWithoutXPartialHeaderReturnsFullHtmlDocument (): void {
        $page = new Page(1, 'about', 'About', null, PageStatus::Published, []);
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('findPublishedByPath')
                    ->willReturn($page)
        );
        $this->mockLayout(fn ($layout) => $layout->method('render')->willReturn('<!DOCTYPE html><title>About</title>'));

        $response = $this->invoke('page', $this->request('/about'));

        $this->assertStringContainsString('<!DOCTYPE html>', $response->body);
    }

    function testPageStillReturns404ForXPartialRequestWhenPageMissing (): void {
        $this->mockLayout(fn ($layout) => null);
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('findPublishedByPath')
                    ->willReturn(null)
        );

        $response = $this->invoke('page', $this->request('/missing', ['x-partial' => '1']));

        $this->assertSame(404, $response->status);
        $this->assertStringContainsString('Page not found', $response->body);
    }

    function testPageStripsLeadingSlashBeforeLookup (): void {
        $this->mockLayout(fn ($layout) => null);
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
        $this->mockLayout(fn ($layout) => null);
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
        $response = (new PublicController($this->createMock(PageService::class), $this->createMock(Layout::class)))
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
        $controller = new PublicController($this->createMock(PageService::class), $this->createMock(Layout::class));

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
        $controller = new PublicController($this->createMock(PageService::class), $this->createMock(Layout::class));

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

    /** @param array<string, string> $headers */
    private function request (string $path, array $headers = []): Request {
        return new Request(Method::GET, new Path($path), new Query(), $headers);
    }

    /** @param Closure(MockObject&PageService) $configuration */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(PageService::class);
        $configuration($service);
        Container::set(PageService::class, $service);
    }

    /**
     * PublicController now depends on Layout too — Container::get autowires
     * the whole graph on construction (regardless of whether the test path
     * actually calls render()), so every test routed through the container
     * needs this registered, not just the ones asserting on Layout::render.
     *
     * @param Closure(MockObject&Layout) $configuration
     */
    private function mockLayout (Closure $configuration): void {
        $layout = $this->createMock(Layout::class);
        $configuration($layout);
        Container::set(Layout::class, $layout);
    }

    private function invoke (string $method, Request $request): Response {
        return (PublicController::handler($method))($request);
    }

}
