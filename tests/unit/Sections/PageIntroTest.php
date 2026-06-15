<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\PageIntro\PageIntroSection;
use TheSaiged\Tests\TestCase;

final class PageIntroTest extends TestCase {

    function testTypeReturnsPageIntro (): void {
        $this->assertSame('page-intro', PageIntroSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = PageIntroSection::fromArray([
            'heading' => 'Studio',
            'body'    => 'The Saiged Studio translates cultural value.',
        ]);

        $this->assertSame('Studio',                                $section->heading);
        $this->assertSame('The Saiged Studio translates cultural value.', $section->body);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['body' => 'text']])]
    #[TestWith([['heading' => 'h']])]
    #[TestWith([['heading' => 1, 'body' => 'text']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        PageIntroSection::fromArray($data);
    }

    function testRenderEscapesContent (): void {
        $section = new PageIntroSection(
            heading: 'Studio <b>test</b>',
            body:    'Text with "quotes"',
        );

        $html = $section->render();

        $this->assertStringContainsString('Studio &lt;b&gt;test&lt;/b&gt;', $html);
        $this->assertStringContainsString('Text with &quot;quotes&quot;',   $html);
    }

    function testRenderContainsCssClasses (): void {
        $section = new PageIntroSection('Studio', 'Body text');
        $html    = $section->render();

        $this->assertStringContainsString('page-intro',         $html);
        $this->assertStringContainsString('page-intro-heading', $html);
        $this->assertStringContainsString('page-intro-body',    $html);
    }

}
