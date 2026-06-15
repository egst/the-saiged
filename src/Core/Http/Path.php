<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http;

use Stringable;

final readonly class Path implements Stringable {

    /** @param array<string, string> $params */
    function __construct (
        public string $value,
        private array $params = [],
    ) {}

    function __toString (): string {
        return $this->value;
    }

    /** @param array<string, string> $params */
    function withParams (array $params): self {
        return new self($this->value, $params);
    }

    function get (string $name): ?string {
        return $this->params[$name] ?? null;
    }

    function getString (string $name): ?string {
        return $this->get($name);
    }

    function getInt (string $name): ?int {
        $value = $this->get($name);
        if ($value === null)
            return null;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed !== false ? $parsed : null;
    }

}
