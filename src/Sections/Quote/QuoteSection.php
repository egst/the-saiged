<?php declare(strict_types = 1);

namespace TheSaiged\Sections\Quote;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class QuoteSection implements Section {

    function __construct (
        public string $quote,
        public string $cite,
    ) {}

    static function type (): string {
        return 'quote';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $quote = $data['quote'] ?? null;
        $cite  = $data['cite']  ?? null;

        if (!is_string($quote) || !is_string($cite))
            throw new InvalidDataException('quote section data');

        return new self(quote: $quote, cite: $cite);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'quote' => $this->quote,
            'cite'  => $this->cite,
        ];
    }

    function render (): string {
        $quote = htmlspecialchars($this->quote, ENT_QUOTES);
        $cite  = htmlspecialchars($this->cite,  ENT_QUOTES);
        $citeHtml = $cite !== ''
            ? "<cite class=\"quote-cite\">$cite</cite>"
            : '';
        return <<<HTML
            <section class="quote">
                <blockquote class="quote-text">$quote</blockquote>
                $citeHtml
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
