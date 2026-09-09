<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Database;
use TheSaiged\Core\Database\Migration;
use TheSaiged\Core\Env;

/**
 * Moves existing image uploads from the old flat layout
 * (data/uploads/{id}/) into the new kind-scoped one
 * (data/uploads/images/{id}/) — see UploadStorage. Driven by the DB
 * (kind = 'image'), not by scanning the disk, and safe to re-run: a
 * missing source dir (already moved, or none ever existed locally) is
 * silently skipped.
 */
final readonly class M007ImagesUnderImagesFolder extends Migration {

    function up (): string {
        return '';
    }

    function run (Database $db): void {
        $root = Env::optional('UPLOADS_DIR', '/var/www/the-saiged/data/uploads');
        @mkdir("$root/images", 0775, true);

        foreach ($db->fetchAll("SELECT id FROM uploads WHERE kind = 'image'") as $row) {
            $id  = (int) $row['id'];
            $old = "$root/$id";
            $new = "$root/images/$id";
            if (is_dir($old) && !is_dir($new))
                rename($old, $new);
        }
    }

}
