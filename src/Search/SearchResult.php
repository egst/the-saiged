<?php declare(strict_types = 1);

namespace TheSaiged\Search;

/** One matched page — snippet is already HTML-safe with `<mark>` around matches. */
final readonly class SearchResult {

    function __construct (
        public string $path,
        public string $title,
        public string $snippet,
    ) {}

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'path'    => $this->path,
            'title'   => $this->title,
            'snippet' => $this->snippet,
        ];
    }

}
