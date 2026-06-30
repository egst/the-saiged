<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ArticleCover;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class ArticleCoverSection implements Section {

    private const VARIANT_WIDTH  = 1920;
    private const VARIANT_HEIGHT = 1080;

    function __construct (
        public int    $uploadId,
        public string $eyebrow,
        public string $heading,
        public string $body,
    ) {}

    static function type (): string {
        return 'article-cover';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $uploadId = $data['uploadId'] ?? null;
        $eyebrow  = $data['eyebrow']  ?? null;
        $heading  = $data['heading']  ?? null;
        $body     = $data['body']     ?? null;

        if (!is_int($uploadId) || !is_string($eyebrow) || !is_string($heading) || !is_string($body))
            throw new InvalidDataException('article-cover section data');

        return new self(uploadId: $uploadId, eyebrow: $eyebrow, heading: $heading, body: $body);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'uploadId' => $this->uploadId,
            'eyebrow'  => $this->eyebrow,
            'heading'  => $this->heading,
            'body'     => $this->body,
        ];
    }

    function render (): string {
        $w       = self::VARIANT_WIDTH;
        $h       = self::VARIANT_HEIGHT;
        $url     = htmlspecialchars("/uploads/{$this->uploadId}/{$w}x{$h}-cover.webp", ENT_QUOTES);
        $style   = "background-image: url($url);";
        $eyebrow = htmlspecialchars($this->eyebrow, ENT_QUOTES);
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        $body    = htmlspecialchars($this->body,    ENT_QUOTES);
        $eyebrowHtml = $eyebrow !== ''
            ? "<p class=\"article-cover-eyebrow\">$eyebrow</p>"
            : '';
        $bodyHtml = $body !== ''
            ? "<p class=\"article-cover-body\">$body</p>"
            : '';
        return <<<HTML
            <section class="article-cover" style="$style">
                <div class="article-cover-content">
                    $eyebrowHtml
                    <h1 class="article-cover-heading">$heading</h1>
                    $bodyHtml
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
