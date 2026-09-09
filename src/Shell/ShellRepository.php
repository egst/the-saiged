<?php declare(strict_types = 1);

namespace TheSaiged\Shell;

use TheSaiged\Core\Database\Database;

/**
 * Data primitives for Shell rows. One row per type, keyed on the type
 * string itself — save() upserts, so the first save for a type inserts
 * the row and every save after that updates it in place.
 */
final readonly class ShellRepository {

    function __construct (
        private Database $db,
    ) {}

    /** Raw JSON payload for $type, or null if nothing has been saved yet. */
    function getData (string $type): ?string {
        $row = $this->db->fetchOne('SELECT data FROM shell WHERE type = :type', [':type' => $type]);
        return $row !== null ? (string) $row['data'] : null;
    }

    /**
     * Upserts by hand (select-then-insert-or-update) rather than
     * `ON DUPLICATE KEY UPDATE` — that's MySQL-only syntax and integration
     * tests run against SQLite; this way the same code path is exercised
     * in both.
     */
    function save (string $type, string $json): void {
        $exists = $this->db->fetchOne('SELECT 1 FROM shell WHERE type = :type', [':type' => $type]) !== null;

        if ($exists)
            $this->db->execute('UPDATE shell SET data = :data WHERE type = :type', [':type' => $type, ':data' => $json]);
        else
            $this->db->execute('INSERT INTO shell (type, data) VALUES (:type, :data)', [':type' => $type, ':data' => $json]);
    }

}
