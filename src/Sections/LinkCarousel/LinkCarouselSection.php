<?php declare(strict_types = 1);

namespace TheSaiged\Sections\LinkCarousel;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

/**
 * Full-viewport hero carousel. Each slide has a background image
 * (referenced by upload id), an eyebrow label, a large title, and an
 * optional CTA button. The render outputs slide divs with data
 * attributes; the public-side JS (link-carousel.js) drives auto-advance
 * and updates the shared content area on each transition.
 *
 * Variant URLs are predictable (/uploads/{id}/1920x1080-cover.webp) —
 * the admin pre-generates them via ensureVariant so no Imagick runs at
 * render time.
 */
final readonly class LinkCarouselSection implements Section {

    private const VARIANT_WIDTH  = 1920;
    private const VARIANT_HEIGHT = 1080;

    /** @param list<LinkCarouselItem> $items */
    function __construct (
        public array $items,
    ) {}

    static function type (): string {
        return 'link-carousel';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $items = $data['items'] ?? null;

        if (!is_array($items) || !array_is_list($items))
            throw new InvalidDataException('link-carousel data', 'items must be a list');

        $parsed = [];
        foreach ($items as $raw) {
            if (!is_array($raw))
                throw new InvalidDataException('link-carousel data', 'each item must be an object');
            $parsed[] = LinkCarouselItem::fromArray($raw);
        }

        return new self(items: $parsed);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'items' => array_map(
                fn (LinkCarouselItem $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }

    function render (): string {
        if (empty($this->items))
            return '<section class="link-carousel"></section>';

        $slides = '';
        $bars   = '';

        foreach ($this->items as $index => $item) {
            $url     = htmlspecialchars(
                sprintf('/uploads/%d/%dx%d-cover.webp', $item->uploadId, self::VARIANT_WIDTH, self::VARIANT_HEIGHT),
                ENT_QUOTES,
            );
            $eyebrow    = htmlspecialchars($item->eyebrow,    ENT_QUOTES);
            $title      = htmlspecialchars($item->title,      ENT_QUOTES);
            $buttonText = htmlspecialchars($item->buttonText, ENT_QUOTES);
            $buttonHref = htmlspecialchars($item->buttonHref, ENT_QUOTES);
            $active     = $index === 0 ? ' is-active' : '';
            $slides    .= "<div class=\"link-carousel-slide$active\" style=\"background-image: url($url);\" data-eyebrow=\"$eyebrow\" data-title=\"$title\" data-button-text=\"$buttonText\" data-button-href=\"$buttonHref\"></div>\n";
            $barActive  = $index === 0 ? ' is-active' : '';
            $bars      .= "<div class=\"link-carousel-progress-line$barActive\"><span></span></div>\n";
        }

        $first      = $this->items[0];
        $eyebrow    = htmlspecialchars($first->eyebrow,    ENT_QUOTES);
        $title      = htmlspecialchars($first->title,      ENT_QUOTES);
        $buttonText = htmlspecialchars($first->buttonText, ENT_QUOTES);
        $buttonHref = htmlspecialchars($first->buttonHref, ENT_QUOTES);
        $hidden     = $first->buttonText === '' ? ' hidden' : '';
        $button     = "<a class=\"link-carousel-button\"$hidden href=\"$buttonHref\">$buttonText</a>";

        return <<<HTML
            <section class="link-carousel">
                $slides
                <div class="link-carousel-content">
                    <p class="link-carousel-eyebrow">$eyebrow</p>
                    <h1 class="link-carousel-title">$title</h1>
                    $button
                </div>
                <div class="link-carousel-progress" aria-hidden="true">
                    $bars
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
        return ['link-carousel.js'];
    }

}
