<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ThreeColumn\ThreeColumnSection;
use TheSaiged\Tests\TestCase;

final class ThreeColumnTest extends TestCase {

    private function validData (): array {
        return [
            'heading' => 'Services',
            'items'   => [
                ['title' => 'Brand Strategy',        'body' => 'Cultural positioning.'],
                ['title' => 'Editorial Production',  'body' => 'Commissioned interviews.'],
                ['title' => 'Partnership Development', 'body' => 'Strategic introductions.'],
            ],
        ];
    }

    function testTypeReturnsThreeColumn (): void {
        $this->assertSame('three-column', ThreeColumnSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ThreeColumnSection::fromArray($this->validData());

        $this->assertSame('Services',      $section->heading);
        $this->assertCount(3,              $section->items);
        $this->assertSame('Brand Strategy',         $section->items[0]['title']);
        $this->assertSame('Cultural positioning.',  $section->items[0]['body']);
        $this->assertSame('Partnership Development', $section->items[2]['title']);
    }

    function testFromArrayAcceptsEmptyItems (): void {
        $section = ThreeColumnSection::fromArray(['heading' => 'H', 'items' => []]);

        $this->assertCount(0, $section->items);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['items' => []]])]
    #[TestWith([['heading' => 'H', 'items' => [['title' => 'T']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['body' => 'B']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['title' => 1, 'body' => 'B']]]])]
    #[TestWith([['heading' => 'H', 'items' => ['not-array']]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ThreeColumnSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = $this->validData();
        $section = ThreeColumnSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new ThreeColumnSection('Services <b>test</b>', [
            ['title' => '"Brand"', 'body' => 'B'],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('Services &lt;b&gt;test&lt;/b&gt;', $html);
        $this->assertStringContainsString('&quot;Brand&quot;',                $html);
    }

    function testRenderProducesItemPerEntry (): void {
        $section = ThreeColumnSection::fromArray($this->validData());
        $html    = $section->render();

        $this->assertStringContainsString('three-column-grid',       $html);
        $this->assertSame(3, substr_count($html, 'three-column-item-title'));
        $this->assertStringContainsString('Brand Strategy',          $html);
        $this->assertStringContainsString('Editorial Production',    $html);
        $this->assertStringContainsString('Partnership Development', $html);
    }

}
