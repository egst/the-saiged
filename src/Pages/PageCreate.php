<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use TheSaiged\Core\InvalidDataException;

/**
 * Payload for PageService::create and ::copy (target). Built from a
 * decoded JSON body via fromArray; throws InvalidDataException on bad
 * shape so the controller can convert it to a 400 once at the boundary.
 *
 * Knows nothing about Request — controller decodes the body to an array
 * and hands it here.
 */
final readonly class PageCreate {

    function __construct (
        public string $path,
        public string $title,
    ) {}

    /**
     * @param  array<string, mixed> $body
     * @throws InvalidDataException on missing / empty / wrong-typed fields
     */
    static function fromArray (array $body): self {
        $path = $body['path'] ?? null;
        if (!is_string($path))
            throw new InvalidDataException('PageCreate', 'path must be a string');

        $title = $body['title'] ?? null;
        if (!is_string($title) || $title === '')
            throw new InvalidDataException('PageCreate', 'title must be a non-empty string');

        return new self($path, $title);
    }

}
