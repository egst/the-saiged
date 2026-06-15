<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http\Exception;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException {

    function __construct (
        public readonly int $status,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

}
