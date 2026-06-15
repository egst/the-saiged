<?php declare(strict_types = 1);

namespace TheSaiged\Sections\PageIntro;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class PageIntroSection implements Section {

    function __construct (
        public string $heading,
        public string $body,
    ) {}

    static function type (): string {
        return 'page-intro';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading = $data['heading'] ?? null;
        $body    = $data['body']    ?? null;

        if (!is_string($heading) || !is_string($body))
            throw new InvalidDataException('page-intro section data');

        return new self(heading: $heading, body: $body);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading' => $this->heading,
            'body'    => $this->body,
        ];
    }

    function render (): string {
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        $body    = htmlspecialchars($this->body,    ENT_QUOTES);
        return <<<HTML
            <section class="page-intro">
                <h1 class="page-intro-heading">$heading</h1>
                <p class="page-intro-body">$body</p>
            </section>
            HTML;
    }

    /** @return list<string> */
    static function cssAssets (): array {
        return ['style.css'];
    }

    /** @return list<string> */
    static function jsAssets (): array {
        return [];
    }

}
