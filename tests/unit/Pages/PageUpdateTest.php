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
            'title'      => 'About',
            'metaDesc'   => 'Hello',
            'status'     => 'published',
            'sections'   => [['type' => 'article', 'data' => ['content' => 'c']]],
            'searchable' => true,
        ]);

        $this->assertSame('About',               $update->title);
        $this->assertSame('Hello',               $update->metaDesc);
        $this->assertSame(PageStatus::Published, $update->status);
        $this->assertCount(1,                    $update->sections);
        $this->assertInstanceOf(ArticleSection::class, $update->sections[0]);
        $this->assertTrue($update->searchable);
    }

    function testFromArrayAcceptsNullMetaDesc (): void {
        $update = PageUpdate::fromArray([
            'title'      => 'X',
            'metaDesc'   => null,
            'status'     => 'draft',
            'sections'   => [],
            'searchable' => false,
        ]);

        $this->assertNull($update->metaDesc);
    }

    function testFromArrayDefaultsMissingMetaDescToNull (): void {
        $update = PageUpdate::fromArray([
            'title'      => 'X',
            'status'     => 'draft',
            'sections'   => [],
            'searchable' => true,
        ]);

        $this->assertNull($update->metaDesc);
    }

    /** @param array<string, mixed> $body */
    #[TestWith([['status' => 'draft', 'sections' => [], 'searchable' => true]],                                                'missing title')]
    #[TestWith([['title' => '',  'status' => 'draft', 'sections' => [], 'searchable' => true]],                                'empty title')]
    #[TestWith([['title' => 'X', 'metaDesc' => 123, 'status' => 'draft', 'sections' => [], 'searchable' => true]],             'non-string metaDesc')]
    #[TestWith([['title' => 'X', 'sections' => [], 'searchable' => true]],                                                     'missing status')]
    #[TestWith([['title' => 'X', 'status' => 'archived', 'sections' => [], 'searchable' => true]],                             'unknown status enum')]
    #[TestWith([['title' => 'X', 'status' => 123, 'sections' => [], 'searchable' => true]],                                    'non-string status')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'searchable' => true]],                                                  'missing sections')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => 'no', 'searchable' => true]],                              'sections not a list')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => [['type' => 'article', 'data' => []]], 'searchable' => true]], 'section data shape')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => [['type' => 'nope', 'data' => []]], 'searchable' => true]], 'unknown section type')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => ['not-an-object'], 'searchable' => true]],                 'section item not object')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => []]],                                                      'missing searchable')]
    #[TestWith([['title' => 'X', 'status' => 'draft', 'sections' => [], 'searchable' => 'yes']],                               'non-bool searchable')]
    function testFromArrayRejectsInvalidShape (array $body): void {
        $this->expectException(InvalidDataException::class);
        PageUpdate::fromArray($body);
    }

}
