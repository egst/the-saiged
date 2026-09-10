<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

use RuntimeException;
use Throwable;

/**
 * Thrown by AdminService::addAdmin when the email already has a row.
 * Domain exception — no HTTP coupling; AdminsController's onError maps
 * it to a 409 ConflictException at the boundary.
 */
final class DuplicateEmailException extends RuntimeException {

    function __construct (
        public readonly string $email,
        ?Throwable $previous = null,
    ) {
        parent::__construct("An admin with email '$email' already exists", 0, $previous);
    }

}
