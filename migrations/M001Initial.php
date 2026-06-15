<?php declare(strict_types = 1);

namespace TheSaiged\Migrations;

use TheSaiged\Core\Database\Migration;

final readonly class M001Initial extends Migration {

    function up (): string {
        return <<<SQL
            CREATE TABLE pages (
                id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                path        VARCHAR(255) NOT NULL UNIQUE,
                title       VARCHAR(255) NOT NULL,
                meta_desc   VARCHAR(500) NULL,
                class_names VARCHAR(255) NULL,
                cms_dir     VARCHAR(255) NOT NULL DEFAULT '/',
                status      ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
                content     JSON NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cms_dir (cms_dir),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL;
    }

}
