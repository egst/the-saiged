<?php declare(strict_types = 1);

namespace TheSaiged\Shell\Footer;

use TheSaiged\Core\InvalidDataException;

final readonly class FooterColumn {

    /** @param list<FooterItem> $items */
    function __construct (
        public string $heading,
        public array  $items,
    ) {}

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): self {
        $heading = $data['heading'] ?? null;
        $items   = $data['items']   ?? null;

        if (!is_string($heading))
            throw new InvalidDataException('footer column', 'heading must be a string');
        if (!is_array($items) || !array_is_list($items))
            throw new InvalidDataException('footer column', 'items must be a list');

        $parsed = [];
        foreach ($items as $raw) {
            if (!is_array($raw))
                throw new InvalidDataException('footer column', 'each item must be an object');
            $parsed[] = FooterItem::fromArray($raw);
        }

        return new self(heading: $heading, items: $parsed);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading' => $this->heading,
            'items'   => array_map(fn (FooterItem $item): array => $item->toArray(), $this->items),
        ];
    }

    function render (): string {
        $heading = $this->heading !== '' ? '<h4>' . htmlspecialchars($this->heading, ENT_QUOTES) . '</h4>' : '';
        $items   = '';
        foreach ($this->items as $item)
            $items .= $item->render() . "\n";

        return <<<HTML
            <div class="contact-footer-column">
                $heading
                <div class="contact-footer-column-items">
                    $items
                </div>
            </div>
            HTML;
    }

}
