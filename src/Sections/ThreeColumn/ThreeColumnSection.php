<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ThreeColumn;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class ThreeColumnSection implements Section {

    /** @param list<array{title: string, body: string}> $items */
    function __construct (
        public string $heading,
        public array  $items,
    ) {}

    static function type (): string {
        return 'three-column';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading  = $data['heading'] ?? null;
        $rawItems = $data['items']   ?? null;

        if (!is_string($heading) || !is_array($rawItems))
            throw new InvalidDataException('three-column section data');

        $items = [];
        foreach ($rawItems as $raw) {
            if (
                !is_array($raw)
                || !isset($raw['title'], $raw['body'])
                || !is_string($raw['title'])
                || !is_string($raw['body'])
            )
                throw new InvalidDataException('three-column item data');
            $items[] = ['title' => $raw['title'], 'body' => $raw['body']];
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
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        $cols    = '';
        foreach ($this->items as $item) {
            $title  = htmlspecialchars($item['title'], ENT_QUOTES);
            $body   = htmlspecialchars($item['body'],  ENT_QUOTES);
            $cols  .= <<<HTML
                <div class="three-column-item">
                    <h3 class="three-column-item-title">$title</h3>
                    <p class="three-column-item-body">$body</p>
                </div>
                HTML;
        }
        return <<<HTML
            <section class="three-column">
                <h2 class="three-column-heading">$heading</h2>
                <div class="three-column-grid">$cols</div>
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
