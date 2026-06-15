<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use RuntimeException;
use Throwable;

/**
 * Thrown by PageRepository when an INSERT / path-change would violate the
 * pages.path UNIQUE constraint. Domain exception — no HTTP coupling. The
 * AdminController's onError maps it to a 409 ConflictException at the
 * boundary.
 */
final class DuplicatePathException extends RuntimeException {

    function __construct (
        public readonly string $path,
        ?Throwable $previous = null,
    ) {
        parent::__construct("A page with path '$path' already exists", 0, $previous);
    }

}
