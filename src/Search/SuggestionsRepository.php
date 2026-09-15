<?php declare(strict_types = 1);

namespace TheSaiged\Search;

use TheSaiged\Core\Database\Database;

/**
 * Single-row table (id is always 1) — see ShellRepository for the same
 * hand-rolled upsert pattern (select-then-insert-or-update, so the same
 * code path runs against both MySQL and the SQLite integration tests).
 */
final readonly class SuggestionsRepository {

    function __construct (
        private Database $db,
    ) {}

    /** Raw JSON payload, or null if nothing has been saved yet. */
    function getData (): ?string {
        $row  = $this->db->fetchOne('SELECT data FROM search_suggestions WHERE id = 1');
        $data = $row['data'] ?? null;
        return is_string($data) ? $data : null;
    }

    function save (string $json): void {
        $exists = $this->db->fetchOne('SELECT 1 FROM search_suggestions WHERE id = 1') !== null;

        if ($exists)
            $this->db->execute('UPDATE search_suggestions SET data = :data WHERE id = 1', [':data' => $json]);
        else
            $this->db->execute('INSERT INTO search_suggestions (id, data) VALUES (1, :data)', [':data' => $json]);
    }

}
