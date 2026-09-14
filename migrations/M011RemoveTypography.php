<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Database;
use TheSaiged\Core\Database\Migration;

/**
 * Reverts M009TypographyFaces + M008UploadsFontKind — the custom-font
 * upload feature (per-role weight/style faces) was removed; the site
 * goes back to a small fixed set of hardcoded fonts instead. Two
 * statements, so run() is overridden rather than joining them into one
 * up() string (PDO here isn't configured for multi-statement exec).
 */
final readonly class M011RemoveTypography extends Migration {

    function up (): string {
        return 'DROP TABLE typography_faces;';
    }

    function run (Database $db): void {
        parent::run($db);
        $db->raw("ALTER TABLE uploads MODIFY kind ENUM('image', 'video') NOT NULL;");
    }

}
