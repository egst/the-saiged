<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Pages;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Pages\PageCreate;
use TheSaiged\Tests\TestCase;

final class PageCreateTest extends TestCase {

    function testFromArrayHappyPath (): void {
        $create = PageCreate::fromArray(['path' => 'about', 'title' => 'About']);

        $this->assertSame('about', $create->path);
        $this->assertSame('About', $create->title);
    }

    function testFromArrayAcceptsEmptyPathAsHomepage (): void {
        $create = PageCreate::fromArray(['path' => '', 'title' => 'Home']);

        $this->assertSame('', $create->path);
        $this->assertSame('Home', $create->title);
    }

    /** @param array<string, mixed> $body */
    #[TestWith([['title' => 'X']],                  'missing path')]
    #[TestWith([['path' => 123,   'title' => 'X']], 'non-string path')]
    #[TestWith([['path' => null,  'title' => 'X']], 'null path')]
    #[TestWith([['path' => 'a']],                   'missing title')]
    #[TestWith([['path' => 'a',   'title' => '']],  'empty title')]
    #[TestWith([['path' => 'a',   'title' => 1]],   'non-string title')]
    #[TestWith([[]],                                'empty body')]
    function testFromArrayRejectsInvalidShape (array $body): void {
        $this->expectException(InvalidDataException::class);
        PageCreate::fromArray($body);
    }

}
