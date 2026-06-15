<?php declare(strict_types = 1);

namespace TheSaiged\Sections\SideList;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class SideListSection implements Section {

    /** @param list<array{title: string, body: string}> $items */
    function __construct (
        public string $heading,
        public string $body,
        public string $linkText,
        public string $linkHref,
        public string $panelHeading,
        public array  $items,
    ) {}

    static function type (): string {
        return 'side-list';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading      = $data['heading']      ?? null;
        $body         = $data['body']         ?? null;
        $linkText     = $data['linkText']     ?? null;
        $linkHref     = $data['linkHref']     ?? null;
        $panelHeading = $data['panelHeading'] ?? null;
        $rawItems     = $data['items']        ?? null;

        if (
            !is_string($heading)
            || !is_string($body)
            || !is_string($linkText)
            || !is_string($linkHref)
            || !is_string($panelHeading)
            || !is_array($rawItems)
        )
            throw new InvalidDataException('side-list section data');

        $items = [];
        foreach ($rawItems as $raw) {
            if (
                !is_array($raw)
                || !isset($raw['title'], $raw['body'])
                || !is_string($raw['title'])
                || !is_string($raw['body'])
            )
                throw new InvalidDataException('side-list item data');
            $items[] = ['title' => $raw['title'], 'body' => $raw['body']];
        }

        return new self(
            heading:      $heading,
            body:         $body,
            linkText:     $linkText,
            linkHref:     $linkHref,
            panelHeading: $panelHeading,
            items:        $items,
        );
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading'      => $this->heading,
            'body'         => $this->body,
            'linkText'     => $this->linkText,
            'linkHref'     => $this->linkHref,
            'panelHeading' => $this->panelHeading,
            'items'        => $this->items,
        ];
    }

    function render (): string {
        $heading      = htmlspecialchars($this->heading,      ENT_QUOTES);
        $panelHeading = htmlspecialchars($this->panelHeading, ENT_QUOTES);

        $paragraphs = array_map(
            fn (string $p): string => '<p class="side-list-body-para">' . htmlspecialchars(trim($p), ENT_QUOTES) . '</p>',
            array_filter(explode("\n\n", $this->body), fn (string $p): bool => trim($p) !== ''),
        );
        $bodyHtml = implode('', $paragraphs);

        $linkHtml = '';
        if ($this->linkText !== '') {
            $text     = htmlspecialchars($this->linkText, ENT_QUOTES);
            $href     = htmlspecialchars($this->linkHref, ENT_QUOTES);
            $linkHtml = "<a class=\"side-list-link\" href=\"$href\">$text</a>";
        }

        $itemsHtml = '';
        foreach ($this->items as $item) {
            $title     = htmlspecialchars($item['title'], ENT_QUOTES);
            $itemBody  = htmlspecialchars($item['body'],  ENT_QUOTES);
            $itemsHtml .= <<<HTML
                <div class="side-list-item">
                    <h4 class="side-list-item-title">$title</h4>
                    <p class="side-list-item-body">$itemBody</p>
                </div>
                HTML;
        }

        return <<<HTML
            <section class="side-list">
                <div class="side-list-inner">
                    <div class="side-list-left">
                        <h2 class="side-list-heading">$heading</h2>
                        $bodyHtml
                        $linkHtml
                    </div>
                    <div class="side-list-right">
                        <div class="side-list-panel">
                            <h3 class="side-list-panel-heading">$panelHeading</h3>
                            $itemsHtml
                        </div>
                    </div>
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
