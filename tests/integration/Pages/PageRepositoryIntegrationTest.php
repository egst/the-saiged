<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Pages;

use PDO;
use RuntimeException;
use TheSaiged\Core\Container;
use TheSaiged\Core\Database\Database;
use TheSaiged\Pages\DuplicatePathException;
use TheSaiged\Pages\Page;
use TheSaiged\Pages\PageRepository;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Pages\PageSummary;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Tests\TestCase;

/**
 * Exercises PageRepository against a real (SQLite in-memory) database.
 *
 * SQLite is functionally compatible enough for the operations we care
 * about — UNIQUE constraint violations surface as SQLSTATE 23000 with a
 * message containing "pages.path", which is what PageRepository's
 * duplicate-path detection looks for.
 *
 * setUp() only primes a fresh PDO + schema. Each test calls
 * Container::get(PageRepository::class) for its own handle.
 */
final class PageRepositoryIntegrationTest extends TestCase {

    function testCreateReturnsNewIdAndPersists (): void {
        $repo = Container::get(PageRepository::class);
        $id   = $repo->create('about', 'About');
        $page = $repo->getById($id);

        $this->assertNotNull($page);
        $this->assertSame('about',           $page->path);
        $this->assertSame('About',           $page->title);
        $this->assertSame(PageStatus::Draft, $page->status, 'New pages default to draft');
        $this->assertSame([],                $page->sections);
    }

    function testCreateOnDuplicatePathThrowsDomainException (): void {
        $repo = Container::get(PageRepository::class);
        $repo->create('about', 'About');
        $this->expectException(DuplicatePathException::class);
        $this->expectExceptionMessageMatches("/path 'about' already exists/");
        $repo->create('about', 'Another about');
    }

    function testInsertWithFullDataPersistsAllFields (): void {
        $repo = Container::get(PageRepository::class);
        $id   = $repo->insert(
            path:       'about',
            title:      'About',
            metaDesc:   'A short description.',
            status:     PageStatus::Published,
            sections:   [new ArticleSection('Hello World')],
            searchable: true,
        );
        $page = $repo->getById($id);

        $this->assertNotNull($page);
        $this->assertSame('A short description.',   $page->metaDesc);
        $this->assertSame(PageStatus::Published,    $page->status);
        $this->assertCount(1,                       $page->sections);
        $this->assertInstanceOf(ArticleSection::class, $page->sections[0]);
        $this->assertSame('Hello World', $page->sections[0]->content);
        $this->assertTrue($page->searchable);
    }

    function testInsertOnDuplicatePathThrowsDomainException (): void {
        $repo = Container::get(PageRepository::class);
        $repo->create('about', 'About');
        $this->expectException(DuplicatePathException::class);
        $repo->insert(
            path:       'about',
            title:      'About (copy)',
            metaDesc:   null,
            status:     PageStatus::Draft,
            sections:   [],
            searchable: true,
        );
    }

    function testSaveUpdatesEditableFields (): void {
        $repo = Container::get(PageRepository::class);
        $id   = $repo->create('about', 'About');
        $page = $repo->getById($id);
        $this->assertNotNull($page);

        $repo->save(new Page(
            id:       $page->id,
            path:     $page->path,
            title:    'About — updated',
            metaDesc: 'New description.',
            status:   PageStatus::Published,
            sections: [new ArticleSection('content')],
        ));

        $loaded = $repo->getById($id);
        $this->assertNotNull($loaded);
        $this->assertSame('About — updated',     $loaded->title);
        $this->assertSame('New description.',    $loaded->metaDesc);
        $this->assertSame(PageStatus::Published, $loaded->status);
        $this->assertCount(1,                    $loaded->sections);
    }

    function testSaveOnUnknownIdThrows (): void {
        $this->expectException(RuntimeException::class);
        Container::get(PageRepository::class)->save(new Page(
            id:       9999,
            path:     'ghost',
            title:    'ghost',
            metaDesc: null,
            status:   PageStatus::Draft,
            sections: [],
        ));
    }

    function testGetPageByIdReturnsNullForUnknown (): void {
        $this->assertNull(Container::get(PageRepository::class)->getById(9999));
    }

    function testGetByPathReturnsAnyMatchRegardlessOfStatus (): void {
        // Repo is pure data — it returns whatever exists at that path,
        // including drafts. Publication-visibility is enforced one layer
        // up in PageService::findPublishedByPath (separate test file).
        $repo  = Container::get(PageRepository::class);
        $repo->create('draft-page', 'Draft');

        $found = $repo->getByPath('draft-page');
        $this->assertNotNull($found);
        $this->assertSame(PageStatus::Draft, $found->status);
        $this->assertNull($repo->getByPath('nonexistent'));
    }

    function testListPagesReturnsAllRowsSortedByTitle (): void {
        $repo = Container::get(PageRepository::class);
        $repo->create('b', 'Banana');
        $repo->create('a', 'Apple');
        $repo->create('c', 'Cherry');

        $list = $repo->listPages();

        $this->assertSame(['Apple', 'Banana', 'Cherry'], array_map(
            fn (PageSummary $p): string => $p->title,
            $list,
        ));
    }

    function testDeleteReturnsTrueWhenRowAffected (): void {
        $repo = Container::get(PageRepository::class);
        $id   = $repo->create('about', 'About');
        $this->assertTrue($repo->delete($id));
        $this->assertNull($repo->getById($id));
    }

    function testDeleteReturnsFalseForUnknownId (): void {
        $this->assertFalse(Container::get(PageRepository::class)->delete(9999));
    }

    function testSearchPublishedMatchesWordAnywhereInSearchText (): void {
        $repo = Container::get(PageRepository::class);
        $repo->insert('about', 'About', null, PageStatus::Published, [new ArticleSection('A modern art gallery.')], true);

        $results = $repo->searchPublished('gallery');

        $this->assertCount(1, $results);
        $this->assertSame('about', $results[0]['path']);
    }

    function testSearchPublishedRequiresEveryWordToMatch (): void {
        $repo = Container::get(PageRepository::class);
        $repo->insert('about', 'About', null, PageStatus::Published, [new ArticleSection('A modern art gallery.')], true);

        $this->assertCount(1, $repo->searchPublished('modern gallery'));
        $this->assertCount(0, $repo->searchPublished('modern missing'));
    }

    function testSearchPublishedIsCaseInsensitive (): void {
        $repo = Container::get(PageRepository::class);
        $repo->insert('about', 'About', null, PageStatus::Published, [new ArticleSection('A Modern Gallery.')], true);

        $this->assertCount(1, $repo->searchPublished('modern'));
    }

    function testSearchPublishedExcludesNonSearchablePages (): void {
        $repo = Container::get(PageRepository::class);
        $repo->insert('about', 'About', null, PageStatus::Published, [new ArticleSection('modern gallery')], false);

        $this->assertSame([], $repo->searchPublished('gallery'));
    }

    function testSearchPublishedExcludesDraftPages (): void {
        $repo = Container::get(PageRepository::class);
        $repo->insert('about', 'About', null, PageStatus::Draft, [new ArticleSection('modern gallery')], true);

        $this->assertSame([], $repo->searchPublished('gallery'));
    }

    function testSearchPublishedExcludesRowsWithNoSearchTextYet (): void {
        // Simulates a page saved before M012PagesSearch existed — the
        // column defaults to NULL there, not an empty string.
        $repo = Container::get(PageRepository::class);
        $id   = $repo->insert('about', 'About', null, PageStatus::Published, [new ArticleSection('modern gallery')], true);
        Container::get(Database::class)->execute('UPDATE pages SET search_text = NULL WHERE id = :id', [':id' => $id]);

        $this->assertSame([], $repo->searchPublished('gallery'));
    }

    function testSearchPublishedReturnsEmptyForBlankQuery (): void {
        $this->assertSame([], Container::get(PageRepository::class)->searchPublished('   '));
    }

    protected function setUp (): void {
        parent::setUp();
        Container::set(PDO::class, self::makeDb());
    }

    private static function makeDb (): PDO {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec(<<<'SQL'
            CREATE TABLE pages (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                path        TEXT NOT NULL UNIQUE,
                title       TEXT NOT NULL,
                meta_desc   TEXT NULL,
                status      TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft', 'published')),
                content     TEXT NULL,
                searchable  INTEGER NOT NULL DEFAULT 1,
                search_text TEXT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        return $pdo;
    }

}
