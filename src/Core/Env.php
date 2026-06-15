<?php declare(strict_types = 1);

namespace TheSaiged\Core;

use RuntimeException;

final class Env {

    static function required (string $key): string {
        $value = getenv($key);
        if (!is_string($value) || $value === '')
            throw new RuntimeException("Missing required env var: $key");
        return $value;
    }

    /** Returns the env var if set + non-empty, otherwise $default. */
    static function optional (string $key, string $default): string {
        $value = getenv($key);
        return is_string($value) && $value !== '' ? $value : $default;
    }

}
