<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Tests\TestCase;

final class ArticleTest extends TestCase {

    function testTypeReturnsArticle (): void {
        $this->assertSame('article', ArticleSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ArticleSection::fromArray(['content' => 'Hello world']);

        $this->assertSame('Hello world', $section->content);
    }

    function testFromArrayAcceptsEmptyContent (): void {
        $section = ArticleSection::fromArray(['content' => '']);

        $this->assertSame('', $section->content);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['content' => 123]])]
    #[TestWith([['content' => null]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ArticleSection::fromArray($data);
    }

    function testToArrayRoundtripsThroughFromArray (): void {
        $original = new ArticleSection(content: 'Hello world');
        $clone    = ArticleSection::fromArray($original->toArray());

        $this->assertSame($original->content, $clone->content);
    }

    function testRenderWrapsParagraphsInPTags (): void {
        $section = new ArticleSection("First paragraph\n\nSecond paragraph");

        $html = $section->render();

        $this->assertStringContainsString('<p class="article-paragraph">First paragraph</p>', $html);
        $this->assertStringContainsString('<p class="article-paragraph">Second paragraph</p>', $html);
    }

    function testRenderConvertsHeadingMarker (): void {
        $section = new ArticleSection("## An Integrated Approach");

        $html = $section->render();

        $this->assertStringContainsString('<h2 class="article-heading">An Integrated Approach</h2>', $html);
        $this->assertStringNotContainsString('##', $html);
    }

    function testRenderConvertsBoldMarker (): void {
        $section = new ArticleSection("This is **important** text.");

        $html = $section->render();

        $this->assertStringContainsString('<strong>important</strong>', $html);
        $this->assertStringNotContainsString('**', $html);
    }

    function testRenderEscapesHtmlInContent (): void {
        $section = new ArticleSection('<script>alert(1)</script>');

        $html = $section->render();

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    function testRenderEscapesHtmlInsideBold (): void {
        $section = new ArticleSection('**<em>bold</em>**');

        $html = $section->render();

        $this->assertStringContainsString('<strong>&lt;em&gt;bold&lt;/em&gt;</strong>', $html);
    }

    function testRenderEmptyContentProducesNoBlockElements (): void {
        $section = new ArticleSection('');

        $html = $section->render();

        $this->assertStringNotContainsString('<p', $html);
        $this->assertStringNotContainsString('<h2', $html);
    }

}
