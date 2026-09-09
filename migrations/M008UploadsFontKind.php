<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Migration;

final readonly class M008UploadsFontKind extends Migration {

    function up (): string {
        return "ALTER TABLE uploads MODIFY kind ENUM('image', 'video', 'font') NOT NULL;";
    }

}
