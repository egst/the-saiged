<?php declare(strict_types = 1);

namespace TheSaiged\Search;

use TheSaiged\Pages\PageRepository;

/**
 * Turns a raw query string into ranked-by-nothing (just title order, via
 * the repository's ORDER BY) search results with a highlighted snippet
 * per page. Matching (PageRepository::searchPublished — a LIKE per word)
 * and highlighting are deliberately decoupled: whatever found the match,
 * highlighting always wraps the literal query words the visitor typed,
 * so what's boxed in the snippet always matches what's in the search
 * input — see notes/ discussion on FULLTEXT's stemming making that less
 * predictable.
 */
final readonly class SearchService {

    /** Characters of context shown before/after the first matched word. */
    private const SNIPPET_RADIUS = 80;

    function __construct (
        private PageRepository $pages,
    ) {}

    /** @return list<SearchResult> */
    function search (string $query): array {
        $words = self::words($query);
        if ($words === [])
            return [];

        $rows = $this->pages->searchPublished($query);

        $results = [];
        foreach ($rows as $row)
            $results[] = new SearchResult(
                path:    $row['path'],
                title:   $row['title'],
                snippet: self::buildSnippet($row['searchText'], $words),
            );
        return $results;
    }

    /** @return list<string> */
    private static function words (string $query): array {
        $split = preg_split('/\s+/', trim($query));
        return array_values(array_filter(
            $split !== false ? $split : [],
            fn (string $word): bool => $word !== '',
        ));
    }

    /** @param list<string> $words */
    private static function buildSnippet (string $searchText, array $words): string {
        $matchPos = null;
        foreach ($words as $word) {
            $pos = mb_stripos($searchText, $word);
            if ($pos !== false && ($matchPos === null || $pos < $matchPos))
                $matchPos = $pos;
        }
        $matchPos ??= 0;

        $length = mb_strlen($searchText);
        $start  = max(0, $matchPos - self::SNIPPET_RADIUS);
        $end    = min($length, $matchPos + self::SNIPPET_RADIUS);
        $raw    = mb_substr($searchText, $start, $end - $start);

        $prefix = $start > 0 ? '…' : '';
        $suffix = $end < $length ? '…' : '';

        return $prefix . self::highlight($raw, $words) . $suffix;
    }

    /**
     * Escapes $text for HTML and wraps every occurrence of any $word in
     * <mark>, all in one pass — splitting on the match pattern first and
     * escaping each resulting piece individually, so the <mark> tags
     * themselves are never escaped but anything that was genuinely `<`
     * or `>` in the original page text still is.
     *
     * @param list<string> $words
     */
    private static function highlight (string $text, array $words): string {
        if ($words === [])
            return htmlspecialchars($text, ENT_QUOTES);

        $alternatives = array_map(fn (string $word): string => preg_quote($word, '/'), $words);
        $pattern      = '/(' . implode('|', $alternatives) . ')/iu';

        $split  = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pieces = $split !== false ? $split : [$text];

        $html = '';
        foreach ($pieces as $index => $piece) {
            $escaped = htmlspecialchars($piece, ENT_QUOTES);
            $html   .= $index % 2 === 1 ? "<mark>$escaped</mark>" : $escaped;
        }
        return $html;
    }

}
