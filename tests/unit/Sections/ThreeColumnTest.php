<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ThreeColumn\ThreeColumnSection;
use TheSaiged\Tests\TestCase;

final class ThreeColumnTest extends TestCase {

    private function validData (): array {
        return [
            'heading'   => 'Services',
            'col1Title' => 'Brand Strategy',
            'col1Body'  => 'Cultural positioning.',
            'col2Title' => 'Editorial Production',
            'col2Body'  => 'Commissioned interviews.',
            'col3Title' => 'Partnership Development',
            'col3Body'  => 'Strategic introductions.',
        ];
    }

    function testTypeReturnsThreeColumn (): void {
        $this->assertSame('three-column', ThreeColumnSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ThreeColumnSection::fromArray($this->validData());

        $this->assertSame('Services',               $section->heading);
        $this->assertSame('Brand Strategy',         $section->col1Title);
        $this->assertSame('Cultural positioning.',  $section->col1Body);
        $this->assertSame('Editorial Production',   $section->col2Title);
        $this->assertSame('Partnership Development', $section->col3Title);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['col1Title' => 't', 'col1Body' => 'b', 'col2Title' => 't', 'col2Body' => 'b', 'col3Title' => 't', 'col3Body' => 'b']])]
    #[TestWith([['heading' => 'h', 'col1Body' => 'b', 'col2Title' => 't', 'col2Body' => 'b', 'col3Title' => 't', 'col3Body' => 'b']])]
    #[TestWith([['heading' => 'h', 'col1Title' => 't', 'col1Body' => 'b', 'col2Title' => 't', 'col2Body' => 'b', 'col3Title' => 't']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ThreeColumnSection::fromArray($data);
    }

    function testRenderEscapesContent (): void {
        $data            = $this->validData();
        $data['heading'] = 'Services <b>test</b>';
        $data['col1Title'] = '"Brand"';

        $html = ThreeColumnSection::fromArray($data)->render();

        $this->assertStringContainsString('Services &lt;b&gt;test&lt;/b&gt;', $html);
        $this->assertStringContainsString('&quot;Brand&quot;',                $html);
    }

    function testRenderContainsAllThreeColumns (): void {
        $section = ThreeColumnSection::fromArray($this->validData());
        $html    = $section->render();

        $this->assertStringContainsString('three-column',            $html);
        $this->assertStringContainsString('three-column-grid',       $html);
        $this->assertStringContainsString('Brand Strategy',          $html);
        $this->assertStringContainsString('Editorial Production',    $html);
        $this->assertStringContainsString('Partnership Development', $html);
    }

}
