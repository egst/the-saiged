<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http;

final readonly class Response {

    /** @param array<string, string> $headers */
    function __construct (
        public int    $status  = 200,
        public array  $headers = [],
        public string $body    = '',
    ) {}

    static function html (string $html, int $status = 200): self {
        return new self(
            status:  $status,
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
            body:    $html,
        );
    }

    static function json (mixed $data, int $status = 200): self {
        return new self(
            status:  $status,
            headers: ['Content-Type' => 'application/json; charset=utf-8'],
            body:    json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    static function text (string $text, int $status = 200): self {
        return new self(
            status:  $status,
            headers: ['Content-Type' => 'text/plain; charset=utf-8'],
            body:    $text,
        );
    }

    static function redirect (string $url, int $status = 302): self {
        return new self(status: $status, headers: ['Location' => $url]);
    }

    function respond (): void {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value)
            header("$name: $value");
        echo $this->body;
    }

}
