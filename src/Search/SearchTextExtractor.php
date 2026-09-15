<?php declare(strict_types = 1);

namespace TheSaiged\Search;

use TheSaiged\Sections\Section;

/**
 * Turns a page's title + meta description + rendered sections into one
 * plain-text blob for `pages.search_text`. Reuses each Section's existing
 * render() rather than adding a dedicated searchableText() to the
 * interface (~20 section types) — cheap to build, good enough for a
 * first pass. Three real quirks that plain strip_tags() alone would
 * introduce, all handled in plainText() below:
 *   - Section::render() always htmlspecialchars()-encodes its content,
 *     so tag removal alone leaves literal `&amp;`, `&#039;`, etc. in the
 *     text — decoded here.
 *   - render()'s heredoc HTML has real newlines/indentation between
 *     tags — collapsed to single spaces so snippets don't look ragged.
 *   - Tags with no separating whitespace (e.g. `<b>Hello</b>world`)
 *     would merge into one word if tags were simply deleted — replacing
 *     each tag with a space instead avoids that.
 * Not indexed: text living in attributes (alt, aria-label, href) — those
 * are removed along with their tag, a known gap, not a bug.
 */
final class SearchTextExtractor {

    /** @param list<Section> $sections */
    static function extract (string $title, ?string $metaDesc, array $sections): string {
        $parts = [$title];
        if ($metaDesc !== null)
            $parts[] = $metaDesc;
        foreach ($sections as $section)
            $parts[] = self::plainText($section->render());

        $nonEmpty = array_filter($parts, fn (string $part): bool => $part !== '');
        return implode(' ', $nonEmpty);
    }

    private static function plainText (string $html): string {
        $spaced  = preg_replace('/<[^>]+>/', ' ', $html) ?? $html;
        $decoded = html_entity_decode($spaced, ENT_QUOTES);
        $collapsed = preg_replace('/\s+/', ' ', $decoded) ?? $decoded;
        return trim($collapsed);
    }

}
