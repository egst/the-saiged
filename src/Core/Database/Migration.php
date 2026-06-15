<?php declare(strict_types = 1);

namespace TheSaiged\Core\Database;

abstract readonly class Migration {

    abstract function up (): string;

    /**
     * Override to run PHP migration logic instead of (or after) the SQL
     * returned by up(). Default implementation just executes up() as raw SQL.
     */
    function run (Database $db): void {
        $db->raw($this->up());
    }

}
