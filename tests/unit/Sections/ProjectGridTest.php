<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ProjectGrid\ProjectGridSection;
use TheSaiged\Tests\TestCase;

final class ProjectGridTest extends TestCase {

    function testTypeReturnsProjectGrid (): void {
        $this->assertSame('project-grid', ProjectGridSection::type());
    }

    function testFromArrayHappyPathEmptyItems (): void {
        $section = ProjectGridSection::fromArray(['items' => []]);
        $this->assertSame([], $section->items);
    }

    function testFromArrayHappyPathWithItems (): void {
        $section = ProjectGridSection::fromArray([
            'items' => [
                ['uploadId' => 1, 'type' => 'Brand Strategy', 'heading' => 'Shell × Ferrari', 'body' => 'Strategic counsel.'],
                ['uploadId' => 2, 'type' => 'Editorial',       'heading' => 'Feature Platform',  'body' => 'Editorial direction.'],
            ],
        ]);

        $this->assertCount(2, $section->items);
        $this->assertSame(1,               $section->items[0]['uploadId']);
        $this->assertSame('Brand Strategy', $section->items[0]['type']);
        $this->assertSame('Shell × Ferrari', $section->items[0]['heading']);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['items' => 'not-array']])]
    #[TestWith([['items' => [['type' => 't', 'heading' => 'h', 'body' => 'b']]]])]
    #[TestWith([['items' => [['uploadId' => 1, 'heading' => 'h', 'body' => 'b']]]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ProjectGridSection::fromArray($data);
    }

    function testRenderEscapesContent (): void {
        $section = new ProjectGridSection([
            ['uploadId' => 1, 'type' => '<script>', 'heading' => '"Project"', 'body' => 'Body'],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('&lt;script&gt;',    $html);
        $this->assertStringContainsString('&quot;Project&quot;', $html);
    }

    function testRenderContainsCssClasses (): void {
        $section = new ProjectGridSection([
            ['uploadId' => 5, 'type' => 'Editorial', 'heading' => 'Test', 'body' => 'Body'],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('project-grid',         $html);
        $this->assertStringContainsString('project-grid-item',    $html);
        $this->assertStringContainsString('project-grid-media',   $html);
        $this->assertStringContainsString('project-grid-heading', $html);
        $this->assertStringContainsString('project-grid-type',    $html);
        $this->assertStringContainsString('/uploads/5/1200x800-cover.webp', $html);
    }

}
