<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Pages;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use TheSaiged\Core\Container;
use TheSaiged\Pages\DuplicatePathException;
use TheSaiged\Pages\Page;
use TheSaiged\Pages\PageCreate;
use TheSaiged\Pages\PageId;
use TheSaiged\Pages\PageRepository;
use TheSaiged\Pages\PageService;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Pages\PageSummary;
use TheSaiged\Pages\PageUpdate;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Tests\TestCase;

/**
 * Unit tests for PageService. PageRepository is mocked (via
 * dg/bypass-finals so PHPUnit can double our final class).
 *
 * Repository behavior — SQL UNIQUE constraints, ORDER BY, status filters
 * in SQL — is covered by PageRepositoryIntegrationTest. Here we only
 * verify what the service layer adds on top: the "published-only"
 * filter in findPublishedByPath, the "lookup-then-mutate" orchestration
 * in update/copy, the bool/null returns for missing rows.
 *
 * Triviální 1-line delegations (list, get, delete, getByPath) jsou
 * v testech naschvál neasertovány — testovaly bychom, že mock byl
 * zavolán s argumenty, které jsme sami předali. Cover repo+wiring jsme
 * udělali jinde.
 */
final class PageServiceTest extends TestCase {

    function testFindPublishedByPathHidesDrafts (): void {
        $draftPage = new Page(1, 'foo', 'Foo', null, PageStatus::Draft, []);
        $this->mockRepo(
            fn ($repo) =>
                $repo
                    ->method('getByPath')
                    ->willReturn($draftPage)
        );

        $this->assertNull(Container::get(PageService::class)->findPublishedByPath('foo'));
    }

    function testFindPublishedByPathReturnsPublishedPages (): void {
        $publishedPage = new Page(1, 'foo', 'Foo', null, PageStatus::Published, []);
        $this->mockRepo(
            fn ($repo) =>
                $repo
                    ->method('getByPath')
                    ->willReturn($publishedPage)
        );

        $this->assertSame(
            $publishedPage,
            Container::get(PageService::class)->findPublishedByPath('foo'),
        );
    }

    function testFindPublishedByPathReturnsNullForUnknownPath (): void {
        $this->mockRepo(
            fn ($repo) =>
                $repo
                    ->method('getByPath')
                    ->willReturn(null)
        );

        $this->assertNull(Container::get(PageService::class)->findPublishedByPath('nope'));
    }

    function testUpdateReturnsFalseWhenIdNotFound (): void {
        $this->mockRepo(
            fn ($repo) =>
                $repo
                    ->method('getById')
                    ->willReturn(null)
        );

        $result = Container::get(PageService::class)->update(
            new PageId(99),
            new PageUpdate('X', null, PageStatus::Draft, []),
        );

        $this->assertFalse($result);
    }

    function testUpdateCallsSaveWithMergedFieldsAndReturnsTrue (): void {
        $existing = new Page(7, 'about', 'About (old)', null, PageStatus::Draft, []);
        $this->mockRepo(function ($repo) use ($existing) {
            $repo->method('getById')->willReturn($existing);
            $repo->expects($this->once())
                ->method('save')
                ->with($this->callback(fn (Page $page) =>
                    $page->id       === 7
                    && $page->path  === 'about'
                    && $page->title === 'About (new)'
                    && $page->status === PageStatus::Published
                ));
        });

        $result = Container::get(PageService::class)->update(
            new PageId(7),
            new PageUpdate('About (new)', null, PageStatus::Published, [new ArticleSection('content')]),
        );

        $this->assertTrue($result);
    }

    function testCopyReturnsNullWhenSourceMissing (): void {
        $this->mockRepo(
            fn ($repo) =>
                $repo
                    ->method('getById')
                    ->willReturn(null)
        );

        $result = Container::get(PageService::class)->copy(
            new PageId(99),
            new PageCreate('new', 'New'),
        );

        $this->assertNull($result);
    }

    function testCopyInsertsAsDraftWithSourceFieldsCarriedOver (): void {
        $source = new Page(
            id:       7,
            path:     'src',
            title:    'Source',
            metaDesc: 'source desc',
            status:   PageStatus::Published,
            sections: [new ArticleSection('content')],
        );
        $this->mockRepo(function ($repo) use ($source) {
            $repo->method('getById')->willReturn($source);
            $repo->expects($this->once())
                ->method('insert')
                ->with(
                    'dst',
                    'Copy',
                    'source desc',
                    PageStatus::Draft,
                    $source->sections,
                )
                ->willReturn(42);
        });

        $result = Container::get(PageService::class)->copy(
            new PageId(7),
            new PageCreate('dst', 'Copy'),
        );

        $this->assertSame(42, $result);
    }

    function testCopyPropagatesDuplicatePathExceptionFromRepo (): void {
        $source = new Page(7, 'src', 'Source', null, PageStatus::Draft, []);
        $this->mockRepo(function ($repo) use ($source) {
            $repo->method('getById')->willReturn($source);
            $repo->method('insert')->willThrowException(new DuplicatePathException('taken'));
        });

        $this->expectException(DuplicatePathException::class);
        Container::get(PageService::class)->copy(new PageId(7), new PageCreate('taken', 'X'));
    }

    function testGetUnwrapsPageIdToInt (): void {
        $page = new Page(42, 'p', 't', null, PageStatus::Draft, []);
        $this->mockRepo(
            fn ($repo) =>
                $repo
                    ->expects($this->once())
                    ->method('getById')
                    ->with(42)
                    ->willReturn($page)
        );

        $this->assertSame($page, Container::get(PageService::class)->get(new PageId(42)));
    }

    function testListReturnsSummariesFromRepo (): void {
        $summaries = [new PageSummary(1, 'a', 'A', PageStatus::Draft)];
        $this->mockRepo(
            fn ($repo) =>
                $repo
                    ->method('listPages')
                    ->willReturn($summaries)
        );

        $this->assertSame($summaries, Container::get(PageService::class)->list());
    }

    /** @param Closure(MockObject&PageRepository) $configuration */
    private function mockRepo (Closure $configuration): void {
        $repo = $this->createMock(PageRepository::class);
        $configuration($repo);
        Container::set(PageRepository::class, $repo);
    }

}
