<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Search;

use TheSaiged\Search\SearchTextExtractor;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Sections\TwoColumn\TwoColumnSection;
use TheSaiged\Tests\TestCase;

final class SearchTextExtractorTest extends TestCase {

    function testCombinesTitleMetaDescAndSectionText (): void {
        $text = SearchTextExtractor::extract(
            title:    'About Us',
            metaDesc: 'A short description.',
            sections: [new ArticleSection('Hello World')],
        );

        $this->assertStringContainsString('About Us',             $text);
        $this->assertStringContainsString('A short description.', $text);
        $this->assertStringContainsString('Hello World',          $text);
    }

    function testOmitsNullMetaDescWithoutError (): void {
        $text = SearchTextExtractor::extract('Title', null, []);

        $this->assertSame('Title', $text);
    }

    function testStripsHtmlTagsFromSectionMarkup (): void {
        $text = SearchTextExtractor::extract('T', null, [new ArticleSection('**Bold** text')]);

        $this->assertStringNotContainsString('<', $text);
        $this->assertStringNotContainsString('>', $text);
    }

    /**
     * The real bug this guards: strip_tags() alone leaves htmlspecialchars()
     * -encoded entities (every Section::render() escapes its content) as
     * literal `&amp;`/`&#039;` in the index and in any snippet built from
     * it — decoded here instead.
     */
    function testDecodesHtmlEntitiesIntroducedByRendering (): void {
        $text = SearchTextExtractor::extract('T', null, [new ArticleSection("R&D's future")]);

        $this->assertStringContainsString("R&D's future", $text);
        $this->assertStringNotContainsString('&amp;',      $text);
        $this->assertStringNotContainsString('&#039;',     $text);
    }

    /**
     * Tags must become a space, not disappear outright — otherwise two
     * words separated only by a tag boundary (e.g. a heading immediately
     * followed by a paragraph with no whitespace between them in the
     * markup) would merge into one unsearchable word.
     */
    function testAdjacentTagsDoNotMergeWords (): void {
        $section = new TwoColumnSection(
            heading:    'Heading',
            body:       'Body',
            buttonText: '',
            buttonHref: '',
        );

        $text = SearchTextExtractor::extract('T', null, [$section]);

        $this->assertStringNotContainsString('HeadingBody', $text);
    }

    function testCollapsesWhitespaceFromRenderedHeredocFormatting (): void {
        $text = SearchTextExtractor::extract('T', null, [new ArticleSection('a')]);

        $this->assertDoesNotMatchRegularExpression('/\s{2,}/', $text);
        $this->assertSame(trim($text), $text);
    }

}
