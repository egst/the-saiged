<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http;

use JsonException;
use TheSaiged\Core\Http\Exception\BadRequestException;

final readonly class Request {

    /** @param array<string, string> $headers */
    function __construct (
        public Method $method,
        public Path   $path,
        public Query  $query,
        # TODO: Header objects... only when we really start using them.
        public array  $headers = [],
        # TODO: Body object with all the getters?
        public mixed  $body    = null,
    ) {}

    static function fromGlobals (): self {
        return new self(
            method:  self::parseMethod(),
            path:    new Path(self::parsePath()),
            query:   Query::fromGlobals(),
            headers: self::collectHeaders(),
            body:    self::parseJsonBody(),
        );
    }

    /** @param array<string, string> $params */
    function withPathParams (array $params): self {
        return new self(
            method:  $this->method,
            path:    $this->path->withParams($params),
            query:   $this->query,
            headers: $this->headers,
            body:    $this->body,
        );
    }

    /** @return ?array<string, mixed> */
    function bodyObject (): ?array {
        if (!is_array($this->body) || array_is_list($this->body))
            return null;
        $result = [];
        foreach ($this->body as $key => $value)
            $result[(string) $key] = $value;
        return $result;
    }

    /** @return ?list<mixed> */
    function bodyList (): ?array {
        return is_array($this->body) && array_is_list($this->body)
            ? $this->body
            : null;
    }

    function header (string $name): ?string {
        return $this->headers[mb_strtolower($name)] ?? null;
    }

    private static function parseMethod (): Method {
        $raw = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $raw = is_string($raw) ? mb_strtoupper($raw) : 'GET';
        return Method::tryFrom($raw) ?? throw new BadRequestException("Unsupported HTTP method: $raw");
    }

    private static function parsePath (): string {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $uri  = is_string($uri) ? $uri : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        // Normalize trailing slash: '/foo/' → '/foo', but keep '/' as is.
        if ($path !== '/' && str_ends_with($path, '/'))
            $path = rtrim($path, '/');

        return $path;
    }

    /** @return array<string, string> */
    private static function collectHeaders (): array {
        $result = [];
        foreach (getallheaders() as $name => $value) {
            if (is_string($name) && is_string($value))
                $result[mb_strtolower($name)] = $value;
        }
        return $result;
    }

    private static function parseJsonBody (): mixed {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '')
            return null;
        try {
            return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new BadRequestException('Invalid JSON body: ' . $jsonException->getMessage(), $jsonException);
        }
    }

}
