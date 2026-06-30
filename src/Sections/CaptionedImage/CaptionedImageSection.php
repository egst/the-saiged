<?php declare(strict_types = 1);

namespace TheSaiged\Sections\CaptionedImage;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class CaptionedImageSection implements Section {

    private const VARIANT_WIDTH  = 1920;
    private const VARIANT_HEIGHT = 1280;

    function __construct (
        public int    $uploadId,
        public string $caption,
    ) {}

    static function type (): string {
        return 'captioned-image';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $uploadId = $data['uploadId'] ?? null;
        $caption  = $data['caption']  ?? null;

        if (!is_int($uploadId) || !is_string($caption))
            throw new InvalidDataException('captioned-image section data');

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
        $w       = self::VARIANT_WIDTH;
        $h       = self::VARIANT_HEIGHT;
        $src     = htmlspecialchars("/uploads/{$this->uploadId}/{$w}x{$h}-cover.webp", ENT_QUOTES);
        $alt     = htmlspecialchars($this->caption, ENT_QUOTES);
        $caption = htmlspecialchars($this->caption, ENT_QUOTES);
        $figcaptionHtml = $caption !== ''
            ? "<figcaption class=\"captioned-image-caption\">$caption</figcaption>"
            : '';
        return <<<HTML
            <figure class="captioned-image">
                <img class="captioned-image-img" src="$src" alt="$alt" loading="lazy">
                $figcaptionHtml
            </figure>
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
