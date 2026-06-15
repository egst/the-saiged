<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use TheSaiged\Core\InvalidDataException;

/**
 * Positive-integer wrapper for page identifiers. The point isn't memory
 * safety — it's making service signatures self-documenting and unable to
 * accept arbitrary integers (e.g., array sizes, counts) where a row id
 * is expected.
 */
final readonly class PageId {

    function __construct (public int $value) {
        if ($value <= 0)
            throw new InvalidDataException('page id', "must be a positive integer, got $value");
    }

}
