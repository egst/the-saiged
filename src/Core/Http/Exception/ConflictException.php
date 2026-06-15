<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http\Exception;

use Throwable;

final class ConflictException extends HttpException {

    function __construct (string $message = 'Conflict', ?Throwable $previous = null) {
        parent::__construct(409, $message, $previous);
    }

}
