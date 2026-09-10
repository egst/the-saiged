<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

use TheSaiged\Core\Database\Database;

final readonly class AdminRepository {

    function __construct (
        private Database $db,
    ) {}

    function findByEmail (string $email): ?AdminRecord {
        $row = $this->db->fetchOne('SELECT email, role FROM admins WHERE email = :email', [':email' => $email]);
        return $row !== null ? AdminRecord::fromDbRow($row) : null;
    }

    /** @return list<AdminRecord> */
    function list (): array {
        $rows = $this->db->fetchAll('SELECT email, role FROM admins ORDER BY created_at, id');
        return array_map(AdminRecord::fromDbRow(...), $rows);
    }

    function count (): int {
        return self::countColumn($this->db->fetchOne('SELECT COUNT(*) AS n FROM admins'));
    }

    function countByRole (AdminRole $role): int {
        return self::countColumn($this->db->fetchOne(
            'SELECT COUNT(*) AS n FROM admins WHERE role = :role',
            [':role' => $role->value],
        ));
    }

    /** @param ?array<string, mixed> $row */
    private static function countColumn (?array $row): int {
        $n = $row['n'] ?? null;
        return is_int($n) ? $n : 0;
    }

    function insert (AdminCreate $create): void {
        $this->db->execute(
            'INSERT INTO admins (email, role) VALUES (:email, :role)',
            [':email' => $create->email, ':role' => $create->role->value],
        );
    }

    function updateRole (string $email, AdminRole $role): bool {
        $affected = $this->db->execute(
            'UPDATE admins SET role = :role WHERE email = :email',
            [':role' => $role->value, ':email' => $email],
        );
        return $affected > 0;
    }

    function delete (string $email): bool {
        $affected = $this->db->execute('DELETE FROM admins WHERE email = :email', [':email' => $email]);
        return $affected > 0;
    }

}
