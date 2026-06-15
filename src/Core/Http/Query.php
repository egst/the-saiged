<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http;

final readonly class Query {

    /** @param array<string, mixed> $params */
    function __construct (
        private array $params = [],
    ) {}

    static function fromGlobals (): self {
        $params = [];
        foreach ($_GET as $key => $value)
            $params[(string) $key] = $value;
        return new self($params);
    }

    function get (string $name): mixed {
        return $this->params[$name] ?? null;
    }

    function getString (string $name): ?string {
        $value = $this->get($name);
        return is_string($value) ? $value : null;
    }

    function getInt (string $name): ?int {
        $value = $this->get($name);
        if ($value === null)
            return null;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed !== false ? $parsed : null;
    }

    /** @return list<string> */
    function getList (string $name): array {
        # TODO: ?foo=x vs ?foo=x&foo=y vs ?foo[]=x&foo[]=y
        # what's the result that PHP gets?
        # if it's impossible to distinguish between single items and a list,
        # or if it's conventional to use single items in place of single-item lists,
        # then this is OK.
        # Otherwise, I'd consider returning null if it's just one item explicitly.
        # What about zero items? I suppose there's no way to distinguish between no input and empty list.
        $value = $this->get($name);
        if (is_string($value))
            return [$value];
        if (is_array($value))
            return array_values(array_filter($value, is_string(...)));
        return [];
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return $this->params;
    }

}
