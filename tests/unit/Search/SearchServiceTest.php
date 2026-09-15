<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Search;

use TheSaiged\Core\Container;
use TheSaiged\Pages\PageRepository;
use TheSaiged\Search\SearchService;
use TheSaiged\Tests\TestCase;

/**
 * PageRepository::searchPublished (the LIKE matching itself, the
 * searchable/published filter) is covered by the integration test —
 * mocked here. What's tested here is SearchService's own job: building
 * a snippet centered on the first matched word, and highlighting every
 * query word inside it with <mark>, safely.
 */
final class SearchServiceTest extends TestCase {

    function testEmptyQueryReturnsNoResultsWithoutHittingTheRepository (): void {
        $repo = $this->createMock(PageRepository::class);
        $repo->expects($this->never())->method('searchPublished');
        Container::set(PageRepository::class, $repo);

        $this->assertSame([], Container::get(SearchService::class)->search('   '));
    }

    function testWrapsMatchedWordInMarkTags (): void {
        $repo = $this->createMock(PageRepository::class);
        $repo->method('searchPublished')->willReturn([
            ['path' => 'about', 'title' => 'About', 'searchText' => 'Hello wonderful world'],
        ]);
        Container::set(PageRepository::class, $repo);

        $results = Container::get(SearchService::class)->search('wonderful');

        $this->assertCount(1, $results);
        $this->assertSame('about',  $results[0]->path);
        $this->assertSame('About', $results[0]->title);
        $this->assertStringContainsString('<mark>wonderful</mark>', $results[0]->snippet);
    }

    function testHighlightingIsCaseInsensitive (): void {
        $repo = $this->createMock(PageRepository::class);
        $repo->method('searchPublished')->willReturn([
            ['path' => 'p', 'title' => 't', 'searchText' => 'Wonderful World'],
        ]);
        Container::set(PageRepository::class, $repo);

        $results = Container::get(SearchService::class)->search('wonderful');

        $this->assertStringContainsString('<mark>Wonderful</mark>', $results[0]->snippet);
    }

    function testMultiWordQueryHighlightsEachWord (): void {
        $repo = $this->createMock(PageRepository::class);
        $repo->method('searchPublished')->willReturn([
            ['path' => 'p', 'title' => 't', 'searchText' => 'a gallery of modern art'],
        ]);
        Container::set(PageRepository::class, $repo);

        $results = Container::get(SearchService::class)->search('gallery art');

        $this->assertStringContainsString('<mark>gallery</mark>', $results[0]->snippet);
        $this->assertStringContainsString('<mark>art</mark>',     $results[0]->snippet);
    }

    /**
     * Snippet text that happens to contain '<' or '>' (decoded from the
     * page's own content by SearchTextExtractor) must come back escaped
     * — only the <mark> wrapper itself is real markup.
     */
    function testSnippetEscapesHtmlFromTheOriginalPageText (): void {
        $repo = $this->createMock(PageRepository::class);
        $repo->method('searchPublished')->willReturn([
            ['path' => 'p', 'title' => 't', 'searchText' => '5 < 10 and wonderful'],
        ]);
        Container::set(PageRepository::class, $repo);

        $results = Container::get(SearchService::class)->search('wonderful');

        $this->assertStringContainsString('&lt;', $results[0]->snippet);
        $this->assertStringNotContainsString('5 < 10', $results[0]->snippet);
    }

    function testLongTextIsTruncatedWithEllipsisAroundTheMatch (): void {
        $padding = str_repeat('filler ', 40);
        $repo    = $this->createMock(PageRepository::class);
        $repo->method('searchPublished')->willReturn([
            ['path' => 'p', 'title' => 't', 'searchText' => $padding . 'wonderful' . $padding],
        ]);
        Container::set(PageRepository::class, $repo);

        $snippet = Container::get(SearchService::class)->search('wonderful')[0]->snippet;

        $this->assertStringStartsWith('…', $snippet);
        $this->assertStringEndsWith('…',   $snippet);
        $this->assertLessThan(strlen($padding) * 2, strlen($snippet));
    }

}
