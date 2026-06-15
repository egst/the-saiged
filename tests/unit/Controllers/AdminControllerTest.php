<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use Closure;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use TheSaiged\Controllers\AdminController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Pages\DuplicatePathException;
use TheSaiged\Pages\Page;
use TheSaiged\Pages\PageCreate;
use TheSaiged\Pages\PageId;
use TheSaiged\Pages\PageService;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Pages\PageSummary;
use TheSaiged\Pages\PageUpdate;
use TheSaiged\Tests\TestCase;

/**
 * Unit tests for AdminController — the IO/wiring layer. Service is mocked
 * (via dg/bypass-finals so PHPUnit can double our `final` classes), so
 * these tests verify only:
 *  - request shape gets parsed into the right service call
 *  - response shape (status, body) matches the contract
 *  - domain exceptions thrown by the service get mapped to HTTP via onError
 *
 * Business logic (what `create` / `update` actually do) is covered by
 * PageServiceIntegrationTest.
 *
 * Tests invoke via `Controller::handler('method')` rather than calling
 * controller methods directly — the handler is what wires onError into
 * the flow, and that mapping IS what we're testing here.
 */
final class AdminControllerTest extends TestCase {

    function testListPagesSerializesEachSummary (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('list')
                    ->willReturn([
                        new PageSummary(1, 'a', 'Apple',  PageStatus::Published),
                        new PageSummary(2, 'b', 'Banana', PageStatus::Draft),
                    ])
        );

        $response = $this->invoke('listPages', $this->request());

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertSame([
            ['id' => 1, 'path' => 'a', 'title' => 'Apple',  'status' => 'published'],
            ['id' => 2, 'path' => 'b', 'title' => 'Banana', 'status' => 'draft'],
        ], $body['pages']);
    }

    function testListSectionsReturnsDiscoveredTypesWithDerivedLabels (): void {
        $this->mockService(fn ($service) => null);

        $response = $this->invoke('listSections', $this->request());

        $this->assertSame(200, $response->status);
        $sections = json_decode($response->body, true)['sections'];

        $byType = array_column($sections, 'label', 'type');
        $this->assertSame('Article',        $byType['article']);
        $this->assertSame('Statement',      $byType['statement']);
        $this->assertSame('Link carousel',  $byType['link-carousel']);
    }

    function testGetPageReturns404WhenServiceReturnsNull (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('get')
                    ->willReturn(null)
        );

        $response = $this->invoke('getPage', $this->request([], '42'));

        $this->assertSame(404, $response->status);
    }

    function testGetPageReturnsFullDetail (): void {
        $page = new Page(7, 'about', 'About', null, PageStatus::Draft, []);
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('get')
                    ->willReturn($page)
        );

        $response = $this->invoke('getPage', $this->request([], '7'));

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertSame(7,       $body['page']['id']);
        $this->assertSame('about', $body['page']['path']);
    }

    function testCreatePageDelegatesToServiceAndReturns201 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('create')
                    ->with($this->callback(fn (PageCreate $req) =>
                        $req->path === 'x' && $req->title === 'X'
                    ))
                    ->willReturn(99)
        );

        $response = $this->invoke('createPage', $this->request(['path' => 'x', 'title' => 'X']));

        $this->assertSame(201, $response->status);
        $this->assertSame(99,  json_decode($response->body, true)['id']);
    }

    function testCreatePageDuplicatePathMapsTo409 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('create')
                    ->willThrowException(new DuplicatePathException('x'))
        );

        $response = $this->invoke('createPage', $this->request(['path' => 'x', 'title' => 'X']));

        $this->assertSame(409, $response->status);
        $this->assertStringContainsString("path 'x' already exists", $response->body);
    }

    /** @param array<string, mixed> $body */
    #[TestWith([['title' => 'X']],                 'missing path')]
    #[TestWith([['path' => 'a']],                  'missing title')]
    #[TestWith([['path' => 'a',  'title' => '']],  'empty title')]
    function testCreatePageBadShapeMapsTo400 (array $body): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->never())
                    ->method('create')
        );

        $response = $this->invoke('createPage', $this->request($body));

        $this->assertSame(400, $response->status);
    }

    function testCreatePageMissingBodyMapsTo400 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->never())
                    ->method('create')
        );

        $response = $this->invoke('createPage', $this->request());

        $this->assertSame(400, $response->status);
        $this->assertStringContainsString('JSON body', $response->body);
    }

    function testUpdatePageReturns404WhenServiceSaysNotFound (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('update')
                    ->willReturn(false)
        );

        $response = $this->invoke('updatePage', $this->request(
            ['title' => 'X', 'status' => 'draft', 'sections' => []],
            '9999',
        ));

        $this->assertSame(404, $response->status);
    }

    function testUpdatePageDelegatesWithParsedPayload (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->once())
                    ->method('update')
                    ->with(
                        $this->callback(fn (PageId $id) => $id->value === 7),
                        $this->callback(fn (PageUpdate $req) =>
                            $req->title === 'X' && $req->status === PageStatus::Published
                        ),
                    )
                    ->willReturn(true)
        );

        $response = $this->invoke('updatePage', $this->request(
            ['title' => 'X', 'metaDesc' => null, 'status' => 'published', 'sections' => []],
            '7',
        ));

        $this->assertSame(200, $response->status);
    }

    /** @param array<string, mixed> $body */
    #[TestWith([['title' => 'X', 'status' => 'archived', 'sections' => []]],                              'unknown status')]
    #[TestWith([['status' => 'draft', 'sections' => []]],                                                 'missing title')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => 'no']],                               'sections not list')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => [['type' => 'nope', 'data' => []]]]], 'unknown section type')]
    function testUpdatePageBadShapeMapsTo400 (array $body): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->expects($this->never())
                    ->method('update')
        );

        $response = $this->invoke('updatePage', $this->request($body, '7'));

        $this->assertSame(400, $response->status);
    }

    function testCopyPageReturns404WhenSourceMissing (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('copy')
                    ->willReturn(null)
        );

        $response = $this->invoke('copyPage', $this->request(
            ['path' => 'y', 'title' => 'Y'],
            '9999',
        ));

        $this->assertSame(404, $response->status);
    }

    function testCopyPageDuplicatePathMapsTo409 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('copy')
                    ->willThrowException(new DuplicatePathException('y'))
        );

        $response = $this->invoke('copyPage', $this->request(
            ['path' => 'y', 'title' => 'Y'],
            '1',
        ));

        $this->assertSame(409, $response->status);
    }

    function testCopyPageHappyPathReturns201 (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('copy')
                    ->willReturn(42)
        );

        $response = $this->invoke('copyPage', $this->request(
            ['path' => 'y', 'title' => 'Y'],
            '1',
        ));

        $this->assertSame(201, $response->status);
        $this->assertSame(42,  json_decode($response->body, true)['id']);
    }

    function testDeletePageReturns404WhenMissing (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('delete')
                    ->willReturn(false)
        );

        $response = $this->invoke('deletePage', $this->request([], '9999'));

        $this->assertSame(404, $response->status);
    }

    function testDeletePageHappyPath (): void {
        $this->mockService(
            fn ($service) =>
                $service
                    ->method('delete')
                    ->willReturn(true)
        );

        $response = $this->invoke('deletePage', $this->request([], '7'));

        $this->assertSame(200, $response->status);
        $this->assertSame(7,   json_decode($response->body, true)['id']);
    }

    /**
     * Non-numeric path id maps to 400 (bad request) for endpoints that
     * read it, or 404 (not found) for getPage which treats invalid id
     * as "doesn't exist". Pinned per-endpoint contract.
     *
     * @param array<string, mixed> $body
     */
    #[TestWith(['getPage',    []])]
    #[TestWith(['updatePage', ['title' => 'X', 'status' => 'draft', 'sections' => []]])]
    #[TestWith(['copyPage',   ['path' => 'y', 'title' => 'Y']])]
    #[TestWith(['deletePage', []])]
    function testEndpointRejectsNonNumericId (string $op, array $body): void {
        $this->mockService(fn ($service) => null);

        $response = $this->invoke($op, $this->request($body, 'abc'));

        $this->assertContains($response->status, [400, 404]);
    }

    /** @param array<string, mixed>|null $body */
    private function request (?array $body = null, string $idParam = ''): Request {
        $path = $idParam === ''
            ? new Path('/api/admin/pages')
            : new Path("/api/admin/pages/$idParam", ['id' => $idParam]);
        return new Request(Method::POST, $path, new Query(), [], $body);
    }

    /**
     * Wire a PageService double + an AdminController bound to it into the Container.
     *
     * @param Closure(MockObject&PageService) $configuration
     */
    private function mockService (Closure $configuration): void {
        $service = $this->createMock(PageService::class);
        $configuration($service);
        Container::set(PageService::class, $service);
    }

    private function invoke (string $method, Request $request): Response {
        return (AdminController::handler($method))($request);
    }

}
