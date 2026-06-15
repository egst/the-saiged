<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Pages;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Pages\Page;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Tests\TestCase;

final class PageTest extends TestCase {

    function testFromDbRowHappyPathWithoutContent (): void {
        $page = Page::fromDbRow([
            'id'      => 7,
            'path'    => 'about',
            'title'   => 'About',
            'status'  => 'draft',
            'content' => null,
        ]);

        $this->assertSame(7,                 $page->id);
        $this->assertSame('about',           $page->path);
        $this->assertSame('About',           $page->title);
        $this->assertNull  (                 $page->metaDesc);
        $this->assertSame(PageStatus::Draft, $page->status);
        $this->assertSame([],                $page->sections);
    }

    function testFromDbRowReadsMetaDesc (): void {
        $page = Page::fromDbRow([
            'id'        => 1,
            'path'      => 'p',
            'title'     => 't',
            'meta_desc' => 'A short description.',
            'status'    => 'published',
            'content'   => null,
        ]);

        $this->assertSame('A short description.', $page->metaDesc);
        $this->assertSame(PageStatus::Published,  $page->status);
    }

    function testFromDbRowDecodesContentSections (): void {
        $page = Page::fromDbRow([
            'id'      => 1,
            'path'    => 'p',
            'title'   => 't',
            'status'  => 'draft',
            'content' => '[{"type":"article","data":{"content":"hello"}}]',
        ]);

        $this->assertCount(1, $page->sections);
        $this->assertInstanceOf(ArticleSection::class, $page->sections[0]);
        $this->assertSame('hello', $page->sections[0]->content);
    }

    /** @param array<string, mixed> $row */
    #[TestWith([['id' => '7', 'path' => 'p', 'title' => 't', 'status' => 'draft', 'content' => null]])]
    #[TestWith([['id' => 7,   'path' => 1,   'title' => 't', 'status' => 'draft', 'content' => null]])]
    #[TestWith([['id' => 7,   'path' => 'p', 'title' => 1,   'status' => 'draft', 'content' => null]])]
    #[TestWith([['path' => 'p', 'title' => 't', 'status' => 'draft', 'content' => null]])]
    #[TestWith([['id' => 7, 'title' => 't', 'status' => 'draft', 'content' => null]])]
    #[TestWith([['id' => 7, 'path' => 'p', 'status' => 'draft', 'content' => null]])]
    #[TestWith([['id' => 7, 'path' => 'p', 'title' => 't', 'content' => null]])]
    #[TestWith([['id' => 7, 'path' => 'p', 'title' => 't', 'status' => 'unknown', 'content' => null]])]
    #[TestWith([['id' => 7, 'path' => 'p', 'title' => 't', 'status' => 'draft', 'meta_desc' => 123, 'content' => null]])]
    function testFromDbRowThrowsOnInvalidShape (array $row): void {
        $this->expectException(InvalidDataException::class);
        Page::fromDbRow($row);
    }

    /** @param array<string, mixed> $row */
    #[TestWith([['id' => 1, 'path' => 'p', 'title' => 't', 'status' => 'draft', 'content' => 123]])]
    #[TestWith([['id' => 1, 'path' => 'p', 'title' => 't', 'status' => 'draft', 'content' => 'not-json{']])]
    #[TestWith([['id' => 1, 'path' => 'p', 'title' => 't', 'status' => 'draft', 'content' => '"just-a-string"']])]
    #[TestWith([['id' => 1, 'path' => 'p', 'title' => 't', 'status' => 'draft', 'content' => '[123]']])]
    function testFromDbRowThrowsOnInvalidContent (array $row): void {
        $this->expectException(InvalidDataException::class);
        Page::fromDbRow($row);
    }

    function testRenderProducesHtmlWithTitleAndSection (): void {
        $page = new Page(
            id:       1,
            path:     'p',
            title:    'My <title>',
            metaDesc: null,
            status:   PageStatus::Draft,
            sections: [new ArticleSection(content: 'Hello World')],
        );

        $html = $page->render();

        $this->assertStringContainsString('<!DOCTYPE html>',  $html);
        $this->assertStringContainsString('My &lt;title&gt;', $html);
        $this->assertStringContainsString('Hello World',      $html);
        $this->assertStringContainsString('/css/public/main.css', $html);
    }

    function testRenderIncludesMetaDescriptionWhenSet (): void {
        $page = new Page(
            id:       1,
            path:     'p',
            title:    't',
            metaDesc: 'A "quoted" desc & more',
            status:   PageStatus::Draft,
            sections: [],
        );

        $html = $page->render();

        $this->assertStringContainsString('name="description"',                          $html);
        $this->assertStringContainsString('A &quot;quoted&quot; desc &amp; more',        $html);
    }

}
