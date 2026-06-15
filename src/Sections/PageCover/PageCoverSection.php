<?php declare(strict_types = 1);

namespace TheSaiged\Sections\PageCover;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

/**
 * Full-viewport hero section with a background image, eyebrow text, and
 * a large serif heading. Background rendered via a predictable variant URL.
 */
final readonly class PageCoverSection implements Section {

    private const VARIANT_WIDTH  = 1920;
    private const VARIANT_HEIGHT = 1080;

    function __construct (
        public int    $uploadId,
        public string $eyebrow,
        public string $heading,
    ) {}

    static function type (): string {
        return 'page-cover';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $uploadId = $data['uploadId'] ?? null;
        $eyebrow  = $data['eyebrow']  ?? null;
        $heading  = $data['heading']  ?? null;

        if (!is_int($uploadId) || !is_string($eyebrow) || !is_string($heading))
            throw new InvalidDataException('page-cover section data');

        return new self(uploadId: $uploadId, eyebrow: $eyebrow, heading: $heading);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'uploadId' => $this->uploadId,
            'eyebrow'  => $this->eyebrow,
            'heading'  => $this->heading,
        ];
    }

    function render (): string {
        $url     = sprintf(
            '/uploads/%d/%dx%d-cover.webp',
            $this->uploadId,
            self::VARIANT_WIDTH,
            self::VARIANT_HEIGHT,
        );
        $bgStyle = 'background-image: url(' . htmlspecialchars($url, ENT_QUOTES) . ');';
        $eyebrow = htmlspecialchars($this->eyebrow, ENT_QUOTES);
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        return <<<HTML
            <section class="hero-section">
                <div class="hero-card">
                    <div class="hero-slide is-active" style="$bgStyle"></div>
                    <div class="hero-content">
                        <p class="eyebrow">$eyebrow</p>
                        <h1 class="hero-title">$heading</h1>
                    </div>
                    <div class="scroll-indicator" aria-hidden="true"></div>
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
