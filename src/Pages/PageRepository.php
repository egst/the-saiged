<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use PDOException;
use RuntimeException;
use TheSaiged\Core\Database\Database;
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
                'INSERT INTO pages (path, title) VALUES (:path, :title)',
                [':path' => $path, ':title' => $title],
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
    ): int {
        $content = self::encodeSections($sections);
        try {
            $this->db->execute(
                'INSERT INTO pages (path, title, meta_desc, status, content)
                 VALUES (:path, :title, :meta_desc, :status, :content)',
                [
                    ':path'      => $path,
                    ':title'     => $title,
                    ':meta_desc' => $metaDesc,
                    ':status'    => $status->value,
                    ':content'   => $content,
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
        $content  = self::encodeSections($page->sections);
        $affected = $this->db->execute(
            'UPDATE pages
                SET title     = :title,
                    meta_desc = :meta_desc,
                    status    = :status,
                    content   = :content
              WHERE id = :id',
            [
                ':title'     => $page->title,
                ':meta_desc' => $page->metaDesc,
                ':status'    => $page->status->value,
                ':content'   => $content,
                ':id'        => $page->id,
            ],
        );
        if ($affected === 0)
            throw new RuntimeException("Page #{$page->id} not found for update");
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
