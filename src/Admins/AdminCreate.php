<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

use TheSaiged\Core\InvalidDataException;

/**
 * Input VO for AdminRepository::insert / AdminService::addAdmin. Separate
 * from AdminRecord (mirrors PageCreate vs Page) even though the shape is
 * currently identical — insert input and a persisted row are different
 * concerns, and this is where "is this even an email" gets validated.
 */
final readonly class AdminCreate {

    function __construct (
        public string    $email,
        public AdminRole $role,
    ) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            throw new InvalidDataException('admin email', $email);
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): self {
        $email   = $data['email'] ?? null;
        $roleRaw = $data['role']  ?? null;

        if (!is_string($email) || !is_string($roleRaw))
            throw new InvalidDataException('admin create', 'expected email and role strings');

        $role = AdminRole::tryFrom($roleRaw)
            ?? throw new InvalidDataException('admin create', "unknown role: $roleRaw");

        return new self(email: $email, role: $role);
    }

}
