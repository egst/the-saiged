<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http;

use Closure;

final readonly class Route {

    private string $regex;

    /** @param Closure(Request):Response $handler */
    function __construct (
        public ?Method $method,
        public string  $pattern,
        public Closure $handler,
    ) {
        $this->regex = self::makeRegex($pattern);
    }

    /** @param Closure(Request):Response $handler */
    static function get (string $pattern, Closure $handler): self {
        return new self(Method::GET, $pattern, $handler);
    }

    /** @param Closure(Request):Response $handler */
    static function post (string $pattern, Closure $handler): self {
        return new self(Method::POST, $pattern, $handler);
    }

    /** @param Closure(Request):Response $handler */
    static function put (string $pattern, Closure $handler): self {
        return new self(Method::PUT, $pattern, $handler);
    }

    /** @param Closure(Request):Response $handler */
    static function delete (string $pattern, Closure $handler): self {
        return new self(Method::DELETE, $pattern, $handler);
    }

    /** @param Closure(Request):Response $handler */
    static function patch (string $pattern, Closure $handler): self {
        return new self(Method::PATCH, $pattern, $handler);
    }

    /**
     * Matches any HTTP method.
     *
     * @param Closure(Request):Response $handler
     */
    static function any (string $pattern, Closure $handler): self {
        return new self(null, $pattern, $handler);
    }

    /**
     * Invokes the handler if the request matches this route, otherwise returns null.
     */
    function try (Request $request): ?Response {
        if ($this->method !== null && $this->method !== $request->method)
            return null;
        if (preg_match($this->regex, $request->path->value, $matches) !== 1)
            return null;
        $params = [];
        foreach ($matches as $key => $value) if (is_string($key))
            $params[$key] = $value;
        return ($this->handler)($request->withPathParams($params));
    }

    /**
     * Pattern syntax:
     * - /foo/bar    — literal match
     * - /users/{id} — captures one path segment (between slashes or end of path) into $id
     * - /api/*      — trailing wildcard, matches anything to the end of the path (no capture)
     */
    private static function makeRegex (string $pattern): string {
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            fn ($m) => '(?P<' . $m[1] . '>[^/]+)',
            $pattern,
        ) ?? $pattern;
        if (str_ends_with($regex, '*'))
            $regex = substr($regex, 0, -1) . '.*';
        return '#^' . $regex . '$#';
    }

}
