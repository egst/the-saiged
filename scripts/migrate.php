<?php declare(strict_types = 1);

require_once __DIR__ . '/../src/bootstrap.php';

TheSaiged\Core\Database\Migrator::get()
    ->withMigrations(__DIR__ . '/../migrations', 'TheSaiged\\Migrations')
    ->run();
