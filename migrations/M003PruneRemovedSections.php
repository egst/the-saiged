<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Database;
use TheSaiged\Core\Database\Migration;

/**
 * Strips sections of the removed types (text, quote, image-carousel) from
 * every page's content JSON. Pages whose content becomes empty are set to
 * NULL (no sections), matching how PageRepository treats absent content.
 */
final readonly class M003PruneRemovedSections extends Migration {

    private const REMOVED_TYPES = ['text', 'quote', 'image-carousel'];

    function up (): string {
        return '';
    }

    function run (Database $db): void {
        $rows = $db->fetchAll('SELECT id, content FROM pages WHERE content IS NOT NULL');

        foreach ($rows as $row) {
            $sections = json_decode((string) $row['content'], true);
            if (!is_array($sections))
                continue;

            $filtered = array_values(array_filter(
                $sections,
                fn (mixed $s) => is_array($s) && !in_array($s['type'] ?? null, self::REMOVED_TYPES, true),
            ));

            $newContent = count($filtered) > 0 ? json_encode($filtered) : null;

            $db->execute(
                'UPDATE pages SET content = :content WHERE id = :id',
                [':content' => $newContent, ':id' => $row['id']],
            );
        }
    }

}
