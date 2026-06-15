<?php declare(strict_types = 1);

namespace TheSaiged\Core\Database;

use PDO;
use PDOStatement;
use RuntimeException;

final class Database {

    function __construct (
        private PDO $pdo,
    ) {}

    /**
     * Unprepared exec for DDL or multi-statement SQL where prepared statements don't apply.
     * Returns the number of affected rows.
     */
    function raw (string $sql): int {
        $result = $this->pdo->exec($sql);
        return $result !== false ? $result : 0;
    }

    /**
     * @param array<string, mixed> $params
     * @return ?array<string, mixed>
     */
    function fetchOne (string $sql, array $params = []): ?array {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? self::stringKeyed($row) : null;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    function fetchAll (string $sql, array $params = []): array {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            if (is_array($row))
                $result[] = self::stringKeyed($row);
        }
        return $result;
    }

    /** @param array<string, mixed> $params */
    function execute (string $sql, array $params = []): int {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    function lastInsertId (): int {
        return (int) $this->pdo->lastInsertId();
    }

    private function prepare (string $sql): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false)
            throw new RuntimeException("Failed to prepare statement: $sql");
        return $stmt;
    }

    /**
     * @param array<mixed, mixed> $row
     * @return array<string, mixed>
     */
    private static function stringKeyed (array $row): array {
        $result = [];
        foreach ($row as $key => $value)
            $result[(string) $key] = $value;
        return $result;
    }

}
