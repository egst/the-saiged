<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ArtistGrid;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class ArtistGridSection implements Section {

    const VARIANT_WIDTH  = 460;
    const VARIANT_HEIGHT = 400;

    /** @param list<array{uploadId: int|null, name: string, birthYear: string}> $items */
    function __construct (
        public string $heading,
        public array  $items,
    ) {}

    static function type (): string {
        return 'artist-grid';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading  = $data['heading'] ?? null;
        $rawItems = $data['items']   ?? null;

        if (!is_string($heading) || !is_array($rawItems))
            throw new InvalidDataException('artist-grid section data');

        $items = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw) || !array_key_exists('uploadId', $raw))
                throw new InvalidDataException('artist-grid item data');

            $uploadId  = $raw['uploadId'];
            $name      = $raw['name']      ?? null;
            $birthYear = $raw['birthYear'] ?? null;

            if (
                ($uploadId !== null && !is_int($uploadId))
                || !is_string($name)
                || !is_string($birthYear)
            )
                throw new InvalidDataException('artist-grid item data');

            $items[] = ['uploadId' => $uploadId, 'name' => $name, 'birthYear' => $birthYear];
        }

        return new self(heading: $heading, items: $items);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading' => $this->heading,
            'items'   => $this->items,
        ];
    }

    function render (): string {
        $heading   = htmlspecialchars($this->heading, ENT_QUOTES);
        $itemsHtml = '';

        foreach ($this->items as $item) {
            $name      = htmlspecialchars($item['name'],      ENT_QUOTES);
            $birthYear = htmlspecialchars($item['birthYear'], ENT_QUOTES);

            $imageHtml = '';
            if ($item['uploadId'] !== null) {
                $w    = self::VARIANT_WIDTH;
                $h    = self::VARIANT_HEIGHT;
                $src  = htmlspecialchars("/uploads/{$item['uploadId']}/{$w}x{$h}-cover.webp", ENT_QUOTES);
                $imageHtml = "<div class=\"artist-grid-image\"><img src=\"$src\" alt=\"$name\" loading=\"lazy\"></div>";
            } else {
                $imageHtml = '<div class="artist-grid-image artist-grid-image--empty"></div>';
            }

            $bylineHtml = $birthYear !== ''
                ? "<p class=\"artist-grid-birth\">b. $birthYear</p>"
                : '';

            $itemsHtml .= <<<HTML
                <div class="artist-grid-item">
                    $imageHtml
                    <h3 class="artist-grid-name">$name</h3>
                    $bylineHtml
                </div>
                HTML;
        }

        return <<<HTML
            <section class="artist-grid">
                <div class="artist-grid-inner">
                    <h2 class="artist-grid-heading">$heading</h2>
                    <div class="artist-grid-grid">$itemsHtml</div>
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
