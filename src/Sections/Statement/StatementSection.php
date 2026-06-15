<?php declare(strict_types = 1);

namespace TheSaiged\Sections\Statement;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

/**
 * Statement section: a centered dark block with a large serif heading,
 * supporting body text, and an optional light-coloured call-to-action.
 * Button is omitted from the render when buttonText is empty.
 */
final readonly class StatementSection implements Section {

    function __construct (
        public string $heading,
        public string $body,
        public string $buttonText,
        public string $buttonHref,
    ) {}

    static function type (): string {
        return 'statement';
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
            throw new InvalidDataException('statement section data');

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
            $button = "<a class=\"statement-button\" href=\"$href\">$text</a>";
        }
        return <<<HTML
            <section class="statement">
                <div class="statement-inner">
                    <h2 class="statement-heading">$heading</h2>
                    <p class="statement-body">$body</p>
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
