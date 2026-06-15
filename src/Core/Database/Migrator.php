<?php declare(strict_types = 1);

namespace TheSaiged\Core\Database;

use ReflectionClass;
use RuntimeException;
use Throwable;
use TheSaiged\Core\ClassDiscovery;
use TheSaiged\Core\Singleton;

final class Migrator {

    use Singleton;

    private ?string $migrationsDir       = null;
    private ?string $migrationsNamespace = null;

    function __construct (
        private readonly Database $db,
    ) {}

    function withMigrations (string $directory, string $namespace): self {
        $copy = new self($this->db);
        $copy->migrationsDir       = $directory;
        $copy->migrationsNamespace = $namespace;
        return $copy;
    }

    function run (): void {
        if ($this->migrationsDir === null || $this->migrationsNamespace === null)
            throw new RuntimeException('Migrator not configured: call withMigrations(dir, namespace) first.');

        $this->db->raw('
            CREATE TABLE IF NOT EXISTS migrations (
                name       VARCHAR(255) PRIMARY KEY,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $applied = $this->appliedMigrations();
        $classes = ClassDiscovery::inDirectory(
            $this->migrationsDir,
            $this->migrationsNamespace,
            Migration::class,
        );
        sort($classes);
        $ran = 0;

        foreach ($classes as $class) {
            $name = (new ReflectionClass($class))->getShortName();
            if (isset($applied[$name]))
                continue;

            echo "Applying $name... ";
            try {
                $migration = new $class();
                $migration->run($this->db);
                $this->db->execute(
                    'INSERT INTO migrations (name) VALUES (:name)',
                    [':name' => $name],
                );
                echo "ok\n";
                $ran++;
            } catch (Throwable $exception) {
                echo "FAILED\n";
                fwrite(STDERR, $exception->getMessage() . "\n");
                throw $exception;
            }
        }

        echo "Done. $ran migration(s) applied.\n";
    }

    /** @return array<string, true> */
    private function appliedMigrations (): array {
        $applied = [];
        foreach ($this->db->fetchAll('SELECT name FROM migrations') as $row) {
            $name = $row['name'] ?? null;
            if (is_string($name))
                $applied[$name] = true;
        }
        return $applied;
    }

}
