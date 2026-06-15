<?php declare(strict_types = 1);

namespace TheSaiged\Sections\Article;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

/**
 * Editorial text section with minimal Markdown rendering.
 * Supports ## headings, **bold**, and blank-line-separated paragraphs.
 */
final readonly class ArticleSection implements Section {

    function __construct (
        public string $content,
    ) {}

    static function type (): string {
        return 'article';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $content = $data['content'] ?? null;

        if (!is_string($content))
            throw new InvalidDataException('article section data');

        return new self(content: $content);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return ['content' => $this->content];
    }

    function render (): string {
        $inner = self::renderMarkdown($this->content);
        return <<<HTML
            <section class="article">
                <div class="article-inner">
                    $inner
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

    private static function renderMarkdown (string $content): string {
        $blocks = preg_split('/\n\n+/', trim($content)) ?: [];
        $html   = '';
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '')
                continue;
            if (str_starts_with($block, '## ')) {
                $text  = htmlspecialchars(substr($block, 3), ENT_QUOTES);
                $html .= "<h2 class=\"article-heading\">$text</h2>";
            } else {
                $text  = htmlspecialchars($block, ENT_QUOTES);
                $text  = (string) preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
                $html .= "<p class=\"article-paragraph\">$text</p>";
            }
        }
        return $html;
    }

}
