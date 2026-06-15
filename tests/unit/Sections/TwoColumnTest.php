<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\TwoColumn\TwoColumnSection;
use TheSaiged\Tests\TestCase;

final class TwoColumnTest extends TestCase {

    function testTypeReturnsTwoColumn (): void {
        $this->assertSame('two-column', TwoColumnSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = TwoColumnSection::fromArray([
            'heading'    => 'Title',
            'body'       => 'Paragraph',
            'buttonText' => 'Get In Touch',
            'buttonHref' => '/contact',
        ]);

        $this->assertSame('Title',        $section->heading);
        $this->assertSame('Paragraph',    $section->body);
        $this->assertSame('Get In Touch', $section->buttonText);
        $this->assertSame('/contact',     $section->buttonHref);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['body' => 'b', 'buttonText' => '', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'buttonText' => '', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'body' => 'b', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'body' => 'b', 'buttonText' => '']])]
    #[TestWith([['heading' => 1, 'body' => 'b', 'buttonText' => '', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'body' => 'b', 'buttonText' => '', 'buttonHref' => 2]])]
    #[TestWith([[]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        TwoColumnSection::fromArray($data);
    }

    function testRenderEscapesContent (): void {
        $section = new TwoColumnSection('<h>', '<b>', 'Click', '/x');

        $html = $section->render();

        $this->assertStringContainsString('&lt;h&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<h>', $html);
    }

    function testRenderOmitsButtonWhenTextEmpty (): void {
        $section = new TwoColumnSection('H', 'B', '', '/contact');

        $this->assertStringNotContainsString('two-column-button', $section->render());
    }

    function testRenderIncludesButtonWhenTextPresent (): void {
        $section = new TwoColumnSection('H', 'B', 'Click', '/contact');

        $html = $section->render();

        $this->assertStringContainsString('two-column-button', $html);
        $this->assertStringContainsString('href="/contact"', $html);
        $this->assertStringContainsString('Click', $html);
    }

    function testRenderAppendsHardcodedArrowToButton (): void {
        $section = new TwoColumnSection('H', 'B', 'Click', '/contact');

        $this->assertStringContainsString('→', $section->render());
    }

}
