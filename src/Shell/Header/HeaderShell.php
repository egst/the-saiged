<?php declare(strict_types = 1);

namespace TheSaiged\Shell\Header;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Shell;

/**
 * Site header: a row of text links, a logo, and a static search button
 * (no search behaviour yet — just the icon). No menu — overlay navigation
 * isn't part of this shell.
 *
 * Logo follows the same predictable-variant convention every image-bearing
 * section already uses (LinkCarousel, CaptionedImage, …): a cover-fit webp
 * at a fixed size, admin pre-generates it via ensureVariant. No Imagick at
 * render time, no original-file-extension lookup needed.
 */
final readonly class HeaderShell implements Shell {

    const LOGO_WIDTH  = 240;
    const LOGO_HEIGHT = 80;

    /** @param list<HeaderLink> $links */
    function __construct (
        public array $links,
        public ?int  $logoUploadId,
    ) {}

    static function type (): string {
        return 'header';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $links        = $data['links'] ?? null;
        $logoUploadId = $data['logoUploadId'] ?? null;

        if (!is_array($links) || !array_is_list($links))
            throw new InvalidDataException('header data', 'links must be a list');
        if ($logoUploadId !== null && !is_int($logoUploadId))
            throw new InvalidDataException('header data', 'logoUploadId must be an integer or null');

        $parsed = [];
        foreach ($links as $raw) {
            if (!is_array($raw))
                throw new InvalidDataException('header data', 'each link must be an object');
            $parsed[] = HeaderLink::fromArray($raw);
        }

        return new self(links: $parsed, logoUploadId: $logoUploadId);
    }

    static function default (): static {
        return new self(links: [], logoUploadId: null);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'links'        => array_map(fn (HeaderLink $link): array => $link->toArray(), $this->links),
            'logoUploadId' => $this->logoUploadId,
        ];
    }

    function render (): string {
        $links = '';
        foreach ($this->links as $link) {
            $label = htmlspecialchars($link->label, ENT_QUOTES);
            $href  = htmlspecialchars($link->href,  ENT_QUOTES);
            $links .= "<a href=\"$href\">$label</a>\n";
        }

        $logo = $this->logoUploadId !== null
            ? '<img src="' . htmlspecialchars(self::logoUrl($this->logoUploadId), ENT_QUOTES) . '" alt="The Saiged">'
            : 'The Saiged';

        return <<<HTML
            <header class="site-header">
                <nav class="header-left-nav" aria-label="Primary navigation">
                    $links
                </nav>
                <a href="/" class="logo">$logo</a>
                <button class="header-search-bar" type="button" aria-label="Search The Saiged">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="10.5" cy="10.5" r="6.5"></circle>
                        <path d="M15.5 15.5L21 21"></path>
                    </svg>
                </button>
                <div class="scroll-progress" aria-hidden="true"><span class="scroll-progress-bar"></span></div>
            </header>
            HTML;
    }

    static function logoUrl (int $uploadId): string {
        return sprintf('/uploads/%d/%dx%d-cover.webp', $uploadId, self::LOGO_WIDTH, self::LOGO_HEIGHT);
    }

    /** @return list<string> */
    static function cssAssets (): array {
        return ['style.css'];
    }

    /** @return list<string> */
    static function jsAssets (): array {
        return ['header.js'];
    }

}
