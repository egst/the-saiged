<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ScrollGallery;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class ScrollGallerySection implements Section {

    const VARIANT_WIDTH  = 680;
    const VARIANT_HEIGHT = 800;

    /** @param list<array{uploadId: int, caption: string}> $items */
    function __construct (
        public array $items,
    ) {}

    static function type (): string {
        return 'scroll-gallery';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $rawItems = $data['items'] ?? null;

        if (!is_array($rawItems))
            throw new InvalidDataException('scroll-gallery section data');

        $items = [];
        foreach ($rawItems as $raw) {
            $uploadId = is_array($raw) ? ($raw['uploadId'] ?? null) : null;
            $caption  = is_array($raw) ? ($raw['caption']  ?? null) : null;

            if (!is_int($uploadId) || !is_string($caption))
                throw new InvalidDataException('scroll-gallery item data');

            $items[] = ['uploadId' => $uploadId, 'caption' => $caption];
        }

        return new self(items: $items);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return ['items' => $this->items];
    }

    function render (): string {
        $w         = self::VARIANT_WIDTH;
        $h         = self::VARIANT_HEIGHT;
        $itemsHtml = '';

        foreach ($this->items as $item) {
            $src     = htmlspecialchars("/uploads/{$item['uploadId']}/{$w}x{$h}-cover.webp", ENT_QUOTES);
            $style   = "background-image: url($src);";
            $caption = htmlspecialchars($item['caption'], ENT_QUOTES);
            $captionHtml = $caption !== ''
                ? "<div class=\"scroll-gallery-caption\">$caption</div>"
                : '';
            $itemsHtml .= <<<HTML
                <div class="scroll-gallery-item" style="$style">$captionHtml</div>
                HTML;
        }

        return <<<HTML
            <div class="scroll-gallery">
                <div class="scroll-gallery-inner">$itemsHtml</div>
            </div>
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
