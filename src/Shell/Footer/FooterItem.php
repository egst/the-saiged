<?php declare(strict_types = 1);

namespace TheSaiged\Shell\Footer;

use TheSaiged\Core\InvalidDataException;

/**
 * A closed union, not a plugin type — deliberately not discovered like
 * Sections/Shell. Three kinds cover everything the footer columns need
 * (link, text, newsletter signup); a fourth kind would still be a small
 * edit here, not a new mechanism.
 *
 * Link and text items render inline within a column; newsletter renders
 * its own block (input + button), so FooterColumn doesn't wrap items in
 * a shared <ul> — each item's render() owns its own markup.
 */
final readonly class FooterItem {

    private function __construct (
        public FooterItemKind $kind,
        public ?string        $label   = null,
        public ?string        $href    = null,
        public ?string        $content = null,
    ) {}

    static function link (string $label, string $href): self {
        return new self(kind: FooterItemKind::Link, label: $label, href: $href);
    }

    static function text (string $content): self {
        return new self(kind: FooterItemKind::Text, content: $content);
    }

    static function newsletter (): self {
        return new self(kind: FooterItemKind::Newsletter);
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): self {
        $kind = FooterItemKind::tryFrom($data['kind'] ?? '')
            ?? throw new InvalidDataException('footer item', 'unknown kind');

        return match ($kind) {
            FooterItemKind::Link       => self::linkFromArray($data),
            FooterItemKind::Text       => self::textFromArray($data),
            FooterItemKind::Newsletter => self::newsletter(),
        };
    }

    /** @param array<mixed, mixed> $data */
    private static function linkFromArray (array $data): self {
        $label = $data['label'] ?? null;
        $href  = $data['href']  ?? null;
        if (!is_string($label) || !is_string($href))
            throw new InvalidDataException('footer link item', 'label and href must be strings');
        return self::link($label, $href);
    }

    /** @param array<mixed, mixed> $data */
    private static function textFromArray (array $data): self {
        $content = $data['content'] ?? null;
        if (!is_string($content))
            throw new InvalidDataException('footer text item', 'content must be a string');
        return self::text($content);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return match ($this->kind) {
            FooterItemKind::Link       => ['kind' => 'link', 'label' => $this->label, 'href' => $this->href],
            FooterItemKind::Text       => ['kind' => 'text', 'content' => $this->content],
            FooterItemKind::Newsletter => ['kind' => 'newsletter'],
        };
    }

    function render (): string {
        return match ($this->kind) {
            FooterItemKind::Link       => $this->renderLink(),
            FooterItemKind::Text       => $this->renderText(),
            FooterItemKind::Newsletter => $this->renderNewsletter(),
        };
    }

    private function renderLink (): string {
        $label = htmlspecialchars((string) $this->label, ENT_QUOTES);
        $href  = htmlspecialchars((string) $this->href,  ENT_QUOTES);
        return "<a href=\"$href\">$label</a>";
    }

    private function renderText (): string {
        return '<p>' . self::renderInlineMarkdown((string) $this->content) . '</p>';
    }

    private function renderNewsletter (): string {
        return <<<HTML
            <form class="footer-newsletter-form" action="#" method="post" onsubmit="return false;">
                <label class="sr-only" for="footerNewsletterEmail">Email Address</label>
                <div class="footer-newsletter-row">
                    <input id="footerNewsletterEmail" type="email" placeholder="Email Address">
                    <button type="submit" aria-label="Subscribe">&rarr;</button>
                </div>
            </form>
            HTML;
    }

    /**
     * Escapes everything, then turns the one allowed `[label](href)` markdown
     * link syntax into a real anchor — so a column heading like "By signing
     * up you agree to the [Privacy Policy](/privacy)." can carry a link
     * without giving admin content a full HTML surface.
     */
    private static function renderInlineMarkdown (string $text): string {
        $escaped = htmlspecialchars($text, ENT_QUOTES);
        return preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            fn (array $match): string => '<a href="' . $match[2] . '">' . $match[1] . '</a>',
            $escaped,
        ) ?? $escaped;
    }

}
