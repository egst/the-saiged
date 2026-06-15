<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Migration;

final readonly class M002Uploads extends Migration {

    function up (): string {
        return <<<SQL
            CREATE TABLE uploads (
                id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                filename    VARCHAR(255)              NOT NULL,
                mime        VARCHAR(100)              NOT NULL,
                kind        ENUM('image', 'video')    NOT NULL,
                size        INT UNSIGNED              NOT NULL,
                width       INT UNSIGNED              NULL,
                height      INT UNSIGNED              NULL,
                uploaded_at TIMESTAMP                 DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_kind        (kind),
                INDEX idx_uploaded_at (uploaded_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL;
    }

}
