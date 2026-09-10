<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

use RuntimeException;
use TheSaiged\Core\Env;

/**
 * Business operations for admin accounts (see AdminGuard for how these
 * feed into route protection). No passwords, no sessions here — Google
 * does authentication; this only ever decides authorization for an
 * email Google has already vouched for.
 */
final readonly class AdminService {

    function __construct (
        private AdminRepository $repo,
    ) {}

    /**
     * Called from the OAuth callback with an email Google has verified.
     * Returns the matching account, or null if this email isn't one.
     *
     * Bootstrap case: if the table is completely empty and $email
     * matches INITIAL_ADMIN_EMAIL, that email is inserted as the first
     * `admin` and returned — this is the only way to get a first account
     * in without direct DB access.
     */
    function isAuthorized (string $email): ?AdminRecord {
        $existing = $this->repo->findByEmail($email);
        if ($existing !== null)
            return $existing;

        if ($this->repo->count() === 0 && $email === Env::optional('INITIAL_ADMIN_EMAIL', '')) {
            $this->repo->insert(new AdminCreate($email, AdminRole::Admin));
            return $this->repo->findByEmail($email);
        }

        return null;
    }

    /** @return list<AdminRecord> */
    function list (): array {
        return $this->repo->list();
    }

    /** @throws DuplicateEmailException */
    function addAdmin (AdminCreate $create): AdminRecord {
        if ($this->repo->findByEmail($create->email) !== null)
            throw new DuplicateEmailException($create->email);

        $this->repo->insert($create);
        return $this->repo->findByEmail($create->email)
            ?? throw new RuntimeException("Failed to read back admin '$create->email'");
    }

    /**
     * @return bool false when no admin exists for $email
     * @throws LastAdminException demoting the last remaining admin
     */
    function updateRole (string $email, AdminRole $role): bool {
        $current = $this->repo->findByEmail($email);
        if ($current === null)
            return false;

        if ($current->role === AdminRole::Admin && $role !== AdminRole::Admin
            && $this->repo->countByRole(AdminRole::Admin) <= 1)
            throw new LastAdminException($email);

        return $this->repo->updateRole($email, $role);
    }

    /**
     * @return bool false when no admin exists for $email
     * @throws LastAdminException removing the last remaining admin
     */
    function removeAdmin (string $email): bool {
        $current = $this->repo->findByEmail($email);
        if ($current === null)
            return false;

        if ($current->role === AdminRole::Admin && $this->repo->countByRole(AdminRole::Admin) <= 1)
            throw new LastAdminException($email);

        return $this->repo->delete($email);
    }

}
