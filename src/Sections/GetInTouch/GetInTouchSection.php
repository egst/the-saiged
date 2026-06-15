<?php declare(strict_types = 1);

namespace TheSaiged\Sections\GetInTouch;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class GetInTouchSection implements Section {

    function __construct (
        public string $heading,
        public string $ctaText,
        public string $ctaHref,
    ) {}

    static function type (): string {
        return 'get-in-touch';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading = $data['heading'] ?? null;
        $ctaText = $data['ctaText'] ?? null;
        $ctaHref = $data['ctaHref'] ?? null;

        if (!is_string($heading) || !is_string($ctaText) || !is_string($ctaHref))
            throw new InvalidDataException('get-in-touch section data');

        return new self(
            heading: $heading,
            ctaText: $ctaText,
            ctaHref: $ctaHref,
        );
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading' => $this->heading,
            'ctaText' => $this->ctaText,
            'ctaHref' => $this->ctaHref,
        ];
    }

    function render (): string {
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        $ctaText = htmlspecialchars($this->ctaText, ENT_QUOTES);
        $ctaHref = htmlspecialchars($this->ctaHref, ENT_QUOTES);

        return <<<HTML
            <section class="contact-footer">
                <div class="contact-footer-header contact-footer-header--standalone">
                    <h2 class="contact-footer-title">$heading</h2>
                    <a href="$ctaHref" class="contact-footer-cta">$ctaText</a>
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
