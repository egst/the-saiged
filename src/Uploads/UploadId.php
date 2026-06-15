<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use TheSaiged\Core\InvalidDataException;

final readonly class UploadId {

    function __construct (public int $value) {
        if ($value <= 0)
            throw new InvalidDataException('upload id', "must be a positive integer, got $value");
    }

}
