<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

use TheSaiged\Core\InvalidDataException;

/**
 * A row from the `admins` table — the return shape for AdminRepository
 * lookups. No `id`: the domain never needs to address an admin by row
 * id, only by email (the identity Google's OAuth callback hands us).
 */
final readonly class AdminRecord {

    function __construct (
        public string    $email,
        public AdminRole $role,
    ) {}

    /** @param array<string, mixed> $row */
    static function fromDbRow (array $row): self {
        $email   = $row['email'] ?? null;
        $roleRaw = $row['role']  ?? null;

        if (!is_string($email) || !is_string($roleRaw))
            throw new InvalidDataException('admin row');

        $role = AdminRole::tryFrom($roleRaw)
            ?? throw new InvalidDataException('admin row', "unknown role: $roleRaw");

        return new self(email: $email, role: $role);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'email' => $this->email,
            'role'  => $this->role->value,
        ];
    }

}
