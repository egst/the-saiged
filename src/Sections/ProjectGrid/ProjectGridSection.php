<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ProjectGrid;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;
use TheSaiged\Uploads\Upload;

final readonly class ProjectGridSection implements Section {

    private const VARIANT_WIDTH  = 1200;
    private const VARIANT_HEIGHT = 800;

    /**
     * @param list<array{uploadId: int, type: string, heading: string, body: string, href: string, openAsOverlay: bool}> $items
     */
    function __construct (
        public array $items,
    ) {}

    static function type (): string {
        return 'project-grid';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $items = $data['items'] ?? null;
        if (!is_array($items))
            throw new InvalidDataException('project-grid section data');

        $parsed = [];
        foreach ($items as $item) {
            if (
                !is_array($item)
                || !is_int($item['uploadId']    ?? null)
                || !is_string($item['type']     ?? null)
                || !is_string($item['heading']  ?? null)
                || !is_string($item['body']     ?? null)
            )
                throw new InvalidDataException('project-grid item data');

            $parsed[] = [
                'uploadId'      => $item['uploadId'],
                'type'          => $item['type'],
                'heading'       => $item['heading'],
                'body'          => $item['body'],
                'href'          => is_string($item['href'] ?? null) ? $item['href'] : '',
                'openAsOverlay' => ($item['openAsOverlay'] ?? false) === true,
            ];
        }

        return new self(items: $parsed);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return ['items' => $this->items];
    }

    function render (): string {
        $rows = '';
        foreach ($this->items as $item) {
            $url     = Upload::coverImageVariantUrlFor(
                $item['uploadId'],
                self::VARIANT_WIDTH,
                self::VARIANT_HEIGHT,
            );
            $imgUrl  = htmlspecialchars($url,            ENT_QUOTES);
            $type    = htmlspecialchars($item['type'],    ENT_QUOTES);
            $heading = htmlspecialchars($item['heading'], ENT_QUOTES);
            $body    = htmlspecialchars($item['body'],    ENT_QUOTES);
            $href    = $item['href'] ?? '';

            if ($href !== '') {
                $hrefAttr    = htmlspecialchars($href, ENT_QUOTES);
                $overlayAttr = ($item['openAsOverlay'] ?? false) ? ' data-overlay' : '';
                $open        = "<a class=\"project-grid-item\" href=\"$hrefAttr\"$overlayAttr>";
                $close       = '</a>';
            } else {
                $open  = '<article class="project-grid-item">';
                $close = '</article>';
            }

            $rows .= <<<HTML
                $open
                    <div class="project-grid-media">
                        <img src="$imgUrl" alt="$heading" loading="lazy">
                    </div>
                    <div class="project-grid-copy">
                        <div class="project-grid-type">$type</div>
                        <h2 class="project-grid-heading">$heading</h2>
                        <p class="project-grid-body">$body</p>
                    </div>
                $close
                HTML;
        }
        return <<<HTML
            <div class="project-grid-divider"></div>
            <section class="project-grid">
                $rows
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
