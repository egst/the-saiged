<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Migration;

final readonly class M006Shell extends Migration {

    function up (): string {
        return <<<SQL
            CREATE TABLE shell (
                type       VARCHAR(32) PRIMARY KEY,
                data       JSON NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL;
    }

}
