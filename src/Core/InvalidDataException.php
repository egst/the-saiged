<?php declare(strict_types = 1);

namespace TheSaiged\Core;

use RuntimeException;
use Throwable;

/**
 * Signals that data from an external source (DB row, deserialized JSON, …)
 * does not match the shape or value rules expected by the code reading it.
 *
 * Extends RuntimeException because the error is detected at runtime against
 * external state, not from a static logic violation in the code itself.
 */
final class InvalidDataException extends RuntimeException {

    function __construct (
        public string  $what,
        public ?string $detail   = null,
        ?Throwable     $previous = null,
    ) {
        $message = "Invalid $what" . ($detail !== null ? ": $detail" : '');
        parent::__construct($message, previous: $previous);
    }

}
