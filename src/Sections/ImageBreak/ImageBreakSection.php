<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ImageBreak;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

/**
 * Full-viewport image section with a small caption bottom-left.
 * Background is rendered via a predictable variant URL — the admin
 * pre-generates the variant via ensureVariant when picking the image,
 * so no Imagick runs at render time.
 */
final readonly class ImageBreakSection implements Section {

    private const VARIANT_WIDTH  = 1920;
    private const VARIANT_HEIGHT = 1080;

    function __construct (
        public int    $uploadId,
        public string $caption,
    ) {}

    static function type (): string {
        return 'image-break';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $uploadId = $data['uploadId'] ?? null;
        $caption  = $data['caption']  ?? null;

        if (!is_int($uploadId) || !is_string($caption))
            throw new InvalidDataException('image-break section data');

        return new self(uploadId: $uploadId, caption: $caption);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'uploadId' => $this->uploadId,
            'caption'  => $this->caption,
        ];
    }

    function render (): string {
        $url     = sprintf(
            '/uploads/%d/%dx%d-cover.webp',
            $this->uploadId,
            self::VARIANT_WIDTH,
            self::VARIANT_HEIGHT,
        );
        $caption = htmlspecialchars($this->caption, ENT_QUOTES);
        $style   = 'background-image: url(' . htmlspecialchars($url, ENT_QUOTES) . ');';
        return <<<HTML
            <section class="image-break" style="$style">
                <span class="image-break-caption">$caption</span>
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
