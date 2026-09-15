<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use PDOException;
use RuntimeException;
use TheSaiged\Core\Database\Database;
use TheSaiged\Search\SearchTextExtractor;
use TheSaiged\Sections\Section;

/**
 * Data primitives for Pages. Returns nullable for "not found" lookups; the
 * only domain exception thrown is DuplicatePathException, which can't be
 * cleanly expressed as a return value (it signals a constraint violation
 * detected mid-insert).
 *
 * Pure data layer — knows nothing about publication-status filtering or
 * HTTP. The "what's public" rule lives one layer up in PageService.
 */
final readonly class PageRepository {

    function __construct (
        private Database $db,
    ) {}

    function getById (int $id): ?Page {
        $row = $this->db->fetchOne(
            'SELECT * FROM pages WHERE id = :id',
            [':id' => $id],
        );
        return $row !== null ? Page::fromDbRow($row) : null;
    }

    function getByPath (string $path): ?Page {
        $row = $this->db->fetchOne(
            'SELECT * FROM pages WHERE path = :path',
            [':path' => $path],
        );
        return $row !== null ? Page::fromDbRow($row) : null;
    }

    /**
     * Lightweight list view: id, path, title, status only — returns the
     * leaner PageSummary projection so callers can't accidentally rely on
     * fields that aren't loaded here.
     *
     * @return list<PageSummary>
     */
    function listPages (): array {
        $rows  = $this->db->fetchAll('SELECT id, path, title, status FROM pages ORDER BY title');
        $pages = [];
        foreach ($rows as $row)
            $pages[] = PageSummary::fromDbRow($row);
        return $pages;
    }

    /**
     * @throws DuplicatePathException when the path is already taken
     */
    function create (string $path, string $title): int {
        try {
            $this->db->execute(
                'INSERT INTO pages (path, title, search_text) VALUES (:path, :title, :search_text)',
                [
                    ':path'        => $path,
                    ':title'       => $title,
                    ':search_text' => SearchTextExtractor::extract($title, null, []),
                ],
            );
        } catch (PDOException $pdoException) {
            self::rethrowDuplicatePath($pdoException, $path);
        }
        return $this->db->lastInsertId();
    }

    /**
     * Insert a new page with full content. Used by the copy flow: existing
     * page is loaded, fields rewritten (path, title, status=draft), all
     * sections deep-copied into the new row.
     *
     * @param  list<Section>            $sections
     * @throws DuplicatePathException   when the path is already taken
     */
    function insert (
        string     $path,
        string     $title,
        ?string    $metaDesc,
        PageStatus $status,
        array      $sections,
        bool       $searchable,
    ): int {
        $content = self::encodeSections($sections);
        try {
            $this->db->execute(
                'INSERT INTO pages (path, title, meta_desc, status, content, searchable, search_text)
                 VALUES (:path, :title, :meta_desc, :status, :content, :searchable, :search_text)',
                [
                    ':path'        => $path,
                    ':title'       => $title,
                    ':meta_desc'   => $metaDesc,
                    ':status'      => $status->value,
                    ':content'     => $content,
                    ':searchable'  => $searchable,
                    ':search_text' => SearchTextExtractor::extract($title, $metaDesc, $sections),
                ],
            );
        } catch (PDOException $pdoException) {
            self::rethrowDuplicatePath($pdoException, $path);
        }
        return $this->db->lastInsertId();
    }

    function delete (int $id): bool {
        $affected = $this->db->execute(
            'DELETE FROM pages WHERE id = :id',
            [':id' => $id],
        );
        return $affected > 0;
    }

    function save (Page $page): void {
        $content = self::encodeSections($page->sections);
        $searchText = SearchTextExtractor::extract($page->title, $page->metaDesc, $page->sections);
        $affected = $this->db->execute(
            'UPDATE pages
                SET title       = :title,
                    meta_desc   = :meta_desc,
                    status      = :status,
                    content     = :content,
                    searchable  = :searchable,
                    search_text = :search_text
              WHERE id = :id',
            [
                ':title'       => $page->title,
                ':meta_desc'   => $page->metaDesc,
                ':status'      => $page->status->value,
                ':content'     => $content,
                ':searchable'  => $page->searchable,
                ':search_text' => $searchText,
                ':id'          => $page->id,
            ],
        );
        if ($affected === 0)
            throw new RuntimeException("Page #{$page->id} not found for update");
    }

    /**
     * Every whitespace-separated word in $query must appear somewhere in
     * search_text (case-insensitive, not necessarily adjacent/in order) —
     * only among searchable, published pages. Rows whose search_text is
     * still NULL (never saved since M012PagesSearch — see
     * scripts/reindex-search.php) can't match a LIKE, so they're silently
     * excluded rather than needing special-casing here.
     *
     * @return list<array{path: string, title: string, searchText: string}>
     */
    function searchPublished (string $query): array {
        $split = preg_split('/\s+/', trim($query));
        $words = array_values(array_filter(
            $split !== false ? $split : [],
            fn (string $word): bool => $word !== '',
        ));
        if ($words === [])
            return [];

        $conditions = [];
        $params     = [':status' => PageStatus::Published->value];
        foreach ($words as $index => $word) {
            $conditions[]          = "search_text LIKE :word$index";
            $params[":word$index"] = '%' . $word . '%';
        }

        $rows = $this->db->fetchAll(
            'SELECT path, title, search_text FROM pages
              WHERE searchable = 1 AND status = :status AND ' . implode(' AND ', $conditions),
            $params,
        );

        $results = [];
        foreach ($rows as $row) {
            $path       = $row['path']        ?? null;
            $title      = $row['title']       ?? null;
            $searchText = $row['search_text'] ?? null;
            if (is_string($path) && is_string($title) && is_string($searchText))
                $results[] = ['path' => $path, 'title' => $title, 'searchText' => $searchText];
        }
        return $results;
    }

    /**
     * Translate a PDO integrity-constraint violation on the `path` column
     * into a domain DuplicatePathException. SQLSTATE 23000 covers UNIQUE
     * / FK / NOT NULL — we only catch unique-on-path here; anything else
     * propagates as-is.
     */
    private static function rethrowDuplicatePath (PDOException $pdoException, string $path): never {
        if ($pdoException->getCode() === '23000'
            && str_contains((string) $pdoException->getMessage(), 'pages.path'))
            throw new DuplicatePathException($path, $pdoException);
        throw $pdoException;
    }

    /** @param list<Section> $sections */
    private static function encodeSections (array $sections): string {
        return json_encode(
            array_map(
                fn (Section $section): array => [
                    'type' => $section::type(),
                    'data' => $section->toArray(),
                ],
                $sections,
            ),
            JSON_THROW_ON_ERROR,
        );
    }

}
