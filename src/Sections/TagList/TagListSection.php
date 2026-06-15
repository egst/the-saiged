<?php declare(strict_types = 1);

namespace TheSaiged\Sections\TagList;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class TagListSection implements Section {

    function __construct (
        public string $heading,
        public string $body,
        public string $tags,
    ) {}

    static function type (): string {
        return 'tag-list';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading = $data['heading'] ?? null;
        $body    = $data['body']    ?? null;
        $tags    = $data['tags']    ?? null;

        if (!is_string($heading) || !is_string($body) || !is_string($tags))
            throw new InvalidDataException('tag-list section data');

        return new self(heading: $heading, body: $body, tags: $tags);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading' => $this->heading,
            'body'    => $this->body,
            'tags'    => $this->tags,
        ];
    }

    function render (): string {
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        $body    = htmlspecialchars($this->body,    ENT_QUOTES);

        $tagItems = '';
        foreach (array_filter(array_map('trim', explode(',', $this->tags))) as $tag) {
            $t        = htmlspecialchars($tag, ENT_QUOTES);
            $tagItems .= "<span class=\"tag-list-tag\">$t</span>";
        }

        return <<<HTML
            <section class="tag-list">
                <div class="tag-list-inner">
                    <h2 class="tag-list-heading">$heading</h2>
                    <p class="tag-list-body">$body</p>
                    <div class="tag-list-chips">$tagItems</div>
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
