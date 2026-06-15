<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ContactList;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class ContactListSection implements Section {

    /**
     * @param list<array{heading: string, body: string, note: string}> $items
     */
    function __construct (
        public string $heading,
        public array  $items,
    ) {}

    static function type (): string {
        return 'contact-list';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading = $data['heading'] ?? null;
        $items   = $data['items']   ?? null;
        if (!is_string($heading) || !is_array($items))
            throw new InvalidDataException('contact-list section data');

        $parsed = [];
        foreach ($items as $item) {
            if (
                !is_array($item)
                || !is_string($item['heading'] ?? null)
                || !is_string($item['body']    ?? null)
                || !is_string($item['note']    ?? null)
            )
                throw new InvalidDataException('contact-list item data');

            $parsed[] = [
                'heading' => $item['heading'],
                'body'    => $item['body'],
                'note'    => $item['note'],
            ];
        }

        return new self(heading: $heading, items: $parsed);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading' => $this->heading,
            'items'   => $this->items,
        ];
    }

    function render (): string {
        $title = htmlspecialchars($this->heading, ENT_QUOTES);
        $rows  = '';
        foreach ($this->items as $item) {
            $heading  = htmlspecialchars($item['heading'], ENT_QUOTES);
            $body     = htmlspecialchars($item['body'],    ENT_QUOTES);
            $body     = preg_replace(
                '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
                '<a href="mailto:$0">$0</a>',
                $body,
            ) ?? $body;
            $noteHtml = '';
            if ($item['note'] !== '') {
                $note     = htmlspecialchars($item['note'], ENT_QUOTES);
                $noteHtml = "<p class=\"contact-list-note\">$note</p>";
            }
            $rows .= <<<HTML
                <div class="contact-list-item">
                    <h2 class="contact-list-heading">$heading</h2>
                    <div class="contact-list-content">
                        <p class="contact-list-body">$body</p>
                        $noteHtml
                    </div>
                </div>
                HTML;
        }
        return <<<HTML
            <section class="contact-list">
                <div class="contact-list-inner">
                    <h1 class="contact-list-title">$title</h1>
                    <div class="contact-list-items">
                        $rows
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
