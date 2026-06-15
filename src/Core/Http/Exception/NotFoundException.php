<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http\Exception;

use Throwable;

final class NotFoundException extends HttpException {

    function __construct (string $message = 'Not found', ?Throwable $previous = null) {
        parent::__construct(404, $message, $previous);
    }

}
