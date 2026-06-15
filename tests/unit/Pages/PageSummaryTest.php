<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Pages;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Pages\PageSummary;
use TheSaiged\Tests\TestCase;

final class PageSummaryTest extends TestCase {

    function testFromDbRowParsesAllFields (): void {
        $summary = PageSummary::fromDbRow([
            'id'     => 7,
            'path'   => 'about',
            'title'  => 'About',
            'status' => 'published',
        ]);

        $this->assertSame(7,                     $summary->id);
        $this->assertSame('about',               $summary->path);
        $this->assertSame('About',               $summary->title);
        $this->assertSame(PageStatus::Published, $summary->status);
    }

    function testToArrayProducesApiShape (): void {
        $summary = new PageSummary(7, 'about', 'About', PageStatus::Draft);

        $this->assertSame([
            'id'     => 7,
            'path'   => 'about',
            'title'  => 'About',
            'status' => 'draft',
        ], $summary->toArray());
    }

    /** @param array<string, mixed> $row */
    #[TestWith([['id' => '7', 'path' => 'p', 'title' => 't', 'status' => 'draft']],   'non-int id')]
    #[TestWith([['path' => 'p', 'title' => 't', 'status' => 'draft']],                'missing id')]
    #[TestWith([['id' => 7, 'path' => 1, 'title' => 't', 'status' => 'draft']],       'non-string path')]
    #[TestWith([['id' => 7, 'title' => 't', 'status' => 'draft']],                    'missing path')]
    #[TestWith([['id' => 7, 'path' => 'p', 'title' => 1, 'status' => 'draft']],       'non-string title')]
    #[TestWith([['id' => 7, 'path' => 'p', 'status' => 'draft']],                     'missing title')]
    #[TestWith([['id' => 7, 'path' => 'p', 'title' => 't']],                          'missing status')]
    #[TestWith([['id' => 7, 'path' => 'p', 'title' => 't', 'status' => 'unknown']],   'unknown status enum')]
    function testFromDbRowRejectsInvalidShape (array $row): void {
        $this->expectException(InvalidDataException::class);
        PageSummary::fromDbRow($row);
    }

}
