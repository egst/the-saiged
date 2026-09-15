<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Migration;

/**
 * Single-row settings blob (id is always 1) — the "naseptávač" list of
 * placeholder strings the search input cycles through while empty.
 * Same shape as the `shell` table, minus the per-type key since there's
 * only ever one row.
 */
final readonly class M013SearchSuggestions extends Migration {

    function up (): string {
        return <<<SQL
            CREATE TABLE search_suggestions (
                id   TINYINT UNSIGNED PRIMARY KEY,
                data JSON NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL;
    }

}
