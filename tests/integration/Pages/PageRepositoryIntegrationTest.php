<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Pages;

use PDO;
use RuntimeException;
use TheSaiged\Core\Container;
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
            path:     'about',
            title:    'About',
            metaDesc: 'A short description.',
            status:   PageStatus::Published,
            sections: [new ArticleSection('Hello World')],
        );
        $page = $repo->getById($id);

        $this->assertNotNull($page);
        $this->assertSame('A short description.',   $page->metaDesc);
        $this->assertSame(PageStatus::Published,    $page->status);
        $this->assertCount(1,                       $page->sections);
        $this->assertInstanceOf(ArticleSection::class, $page->sections[0]);
        $this->assertSame('Hello World', $page->sections[0]->content);
    }

    function testInsertOnDuplicatePathThrowsDomainException (): void {
        $repo = Container::get(PageRepository::class);
        $repo->create('about', 'About');
        $this->expectException(DuplicatePathException::class);
        $repo->insert(
            path:     'about',
            title:    'About (copy)',
            metaDesc: null,
            status:   PageStatus::Draft,
            sections: [],
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
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                path       TEXT NOT NULL UNIQUE,
                title      TEXT NOT NULL,
                meta_desc  TEXT NULL,
                status     TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft', 'published')),
                content    TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        return $pdo;
    }

}
