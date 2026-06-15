<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http\Exception;

use Throwable;

final class BadRequestException extends HttpException {

    function __construct (string $message = 'Bad request', ?Throwable $previous = null) {
        parent::__construct(400, $message, $previous);
    }

}
