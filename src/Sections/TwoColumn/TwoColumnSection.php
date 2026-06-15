<?php declare(strict_types = 1);

namespace TheSaiged\Sections\TwoColumn;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

/**
 * Two-column section: a large heading on the left, a supporting paragraph
 * plus an optional call-to-action button on the right. Collapses to a
 * single column on narrow viewports (handled in style.css).
 *
 * The button is omitted from the render when buttonText is empty, so a
 * section can be heading + paragraph only.
 */
final readonly class TwoColumnSection implements Section {

    function __construct (
        public string $heading,
        public string $body,
        public string $buttonText,
        public string $buttonHref,
    ) {}

    static function type (): string {
        return 'two-column';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading    = $data['heading']    ?? null;
        $body       = $data['body']       ?? null;
        $buttonText = $data['buttonText'] ?? null;
        $buttonHref = $data['buttonHref'] ?? null;

        if (
            !is_string($heading)
            || !is_string($body)
            || !is_string($buttonText)
            || !is_string($buttonHref)
        )
            throw new InvalidDataException('two-column section data');

        return new self(
            heading:    $heading,
            body:       $body,
            buttonText: $buttonText,
            buttonHref: $buttonHref,
        );
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading'    => $this->heading,
            'body'       => $this->body,
            'buttonText' => $this->buttonText,
            'buttonHref' => $this->buttonHref,
        ];
    }

    function render (): string {
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        $body    = htmlspecialchars($this->body,    ENT_QUOTES);
        $button  = '';
        if ($this->buttonText !== '') {
            $text   = htmlspecialchars($this->buttonText, ENT_QUOTES);
            $href   = htmlspecialchars($this->buttonHref, ENT_QUOTES);
            $button = "<a class=\"two-column-button\" href=\"$href\">$text <span class=\"two-column-button-arrow\" aria-hidden=\"true\">→</span></a>";
        }
        return <<<HTML
            <section class="two-column">
                <div class="two-column-heading">
                    <h2>$heading</h2>
                </div>
                <div class="two-column-body">
                    <p>$body</p>
                    $button
                </div>
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
