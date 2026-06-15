<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use RuntimeException;

/**
 * Thrown by UploadService when the server-detected MIME of an uploaded file
 * is outside the supported whitelist. Domain exception — MediaController's
 * onError maps it to a 400 BadRequestException with a user-friendly message.
 */
final class UnsupportedMediaTypeException extends RuntimeException {

    function __construct (public readonly string $mime) {
        parent::__construct("Unsupported file type: $mime");
    }

}
