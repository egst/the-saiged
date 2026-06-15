<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Database;
use TheSaiged\Core\Database\Migration;

final readonly class M004PrunePageFooterSection extends Migration {

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
                fn (mixed $s) => is_array($s) && ($s['type'] ?? null) !== 'page-footer',
            ));

            $newContent = count($filtered) > 0 ? json_encode($filtered) : null;

            $db->execute(
                'UPDATE pages SET content = :content WHERE id = :id',
                [':content' => $newContent, ':id' => $row['id']],
            );
        }
    }

}
