<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

use RuntimeException;
use Throwable;

/**
 * Thrown by AdminService::updateRole / removeAdmin when the operation
 * would demote or remove the last remaining `admin`-role account —
 * the lockout safeguard. Domain exception — no HTTP coupling;
 * AdminsController's onError maps it to a 409 ConflictException.
 */
final class LastAdminException extends RuntimeException {

    function __construct (
        public readonly string $email,
        ?Throwable $previous = null,
    ) {
        parent::__construct("Cannot remove or demote the last remaining admin ('$email')", 0, $previous);
    }

}
