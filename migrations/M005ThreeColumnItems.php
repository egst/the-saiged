<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Database;
use TheSaiged\Core\Database\Migration;

final readonly class M005ThreeColumnItems extends Migration {

    function up (): string {
        return '';
    }

    function run (Database $db): void {
        $rows = $db->fetchAll('SELECT id, content FROM pages WHERE content IS NOT NULL');

        foreach ($rows as $row) {
            $sections = json_decode((string) $row['content'], true);
            if (!is_array($sections))
                continue;

            $changed = false;
            foreach ($sections as &$section) {
                if (!is_array($section) || ($section['type'] ?? null) !== 'three-column')
                    continue;
                $data = $section['data'] ?? [];
                if (!is_array($data) || isset($data['items']))
                    continue;

                $section['data'] = [
                    'heading' => (string) ($data['heading'] ?? ''),
                    'items'   => [
                        ['title' => (string) ($data['col1Title'] ?? ''), 'body' => (string) ($data['col1Body'] ?? '')],
                        ['title' => (string) ($data['col2Title'] ?? ''), 'body' => (string) ($data['col2Body'] ?? '')],
                        ['title' => (string) ($data['col3Title'] ?? ''), 'body' => (string) ($data['col3Body'] ?? '')],
                    ],
                ];
                $changed = true;
            }
            unset($section);

            if (!$changed)
                continue;

            $db->execute(
                'UPDATE pages SET content = :content WHERE id = :id',
                [':content' => json_encode($sections), ':id' => $row['id']],
            );
        }
    }

}
