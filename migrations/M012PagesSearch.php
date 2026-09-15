<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Migration;

/**
 * `searchable` — admin-facing per-page opt-out toggle (defaults to true,
 * so existing pages are searchable the moment this ships).
 * `search_text` — derived plain-text index of the page's title + meta
 * description + section content, recomputed by PageRepository on every
 * save; starts NULL for existing rows until either re-saved or backfilled
 * via scripts/reindex-search.php.
 */
final readonly class M012PagesSearch extends Migration {

    function up (): string {
        return <<<SQL
            ALTER TABLE pages
                ADD COLUMN searchable  BOOLEAN NOT NULL DEFAULT 1,
                ADD COLUMN search_text LONGTEXT NULL,
                ADD INDEX idx_searchable (searchable);
            SQL;
    }

}
