<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Migration;

final readonly class M009TypographyFaces extends Migration {

    function up (): string {
        return <<<SQL
            CREATE TABLE typography_faces (
                id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                role       ENUM('heading', 'text')  NOT NULL,
                upload_id  INT UNSIGNED             NOT NULL,
                weight_min SMALLINT UNSIGNED        NOT NULL,
                weight_max SMALLINT UNSIGNED        NOT NULL,
                style      ENUM('normal', 'italic') NOT NULL,
                FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL;
    }

}
