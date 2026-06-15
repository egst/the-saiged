<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Pages;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Pages\PageUpdate;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Tests\TestCase;

final class PageUpdateTest extends TestCase {

    function testFromArrayHappyPath (): void {
        $update = PageUpdate::fromArray([
            'title'    => 'About',
            'metaDesc' => 'Hello',
            'status'   => 'published',
            'sections' => [['type' => 'article', 'data' => ['content' => 'c']]],
        ]);

        $this->assertSame('About',               $update->title);
        $this->assertSame('Hello',               $update->metaDesc);
        $this->assertSame(PageStatus::Published, $update->status);
        $this->assertCount(1,                    $update->sections);
        $this->assertInstanceOf(ArticleSection::class, $update->sections[0]);
    }

    function testFromArrayAcceptsNullMetaDesc (): void {
        $update = PageUpdate::fromArray([
            'title'    => 'X',
            'metaDesc' => null,
            'status'   => 'draft',
            'sections' => [],
        ]);

        $this->assertNull($update->metaDesc);
    }

    function testFromArrayDefaultsMissingMetaDescToNull (): void {
        $update = PageUpdate::fromArray([
            'title'    => 'X',
            'status'   => 'draft',
            'sections' => [],
        ]);

        $this->assertNull($update->metaDesc);
    }

    /** @param array<string, mixed> $body */
    #[TestWith([['status' => 'draft', 'sections' => []]],                                                'missing title')]
    #[TestWith([['title' => '',  'status' => 'draft', 'sections' => []]],                                'empty title')]
    #[TestWith([['title' => 'X', 'metaDesc' => 123, 'status' => 'draft', 'sections' => []]],             'non-string metaDesc')]
    #[TestWith([['title' => 'X', 'sections' => []]],                                                     'missing status')]
    #[TestWith([['title' => 'X', 'status' => 'archived', 'sections' => []]],                             'unknown status enum')]
    #[TestWith([['title' => 'X', 'status' => 123, 'sections' => []]],                                    'non-string status')]
    #[TestWith([['title' => 'X', 'status' => 'draft']],                                                  'missing sections')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => 'no']],                              'sections not a list')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => [['type' => 'article', 'data' => []]]]], 'section data shape')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => [['type' => 'nope', 'data' => []]]]], 'unknown section type')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => ['not-an-object']]],                 'section item not object')]
    function testFromArrayRejectsInvalidShape (array $body): void {
        $this->expectException(InvalidDataException::class);
        PageUpdate::fromArray($body);
    }

}
