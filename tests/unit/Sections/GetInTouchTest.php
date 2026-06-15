<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\GetInTouch\GetInTouchSection;
use TheSaiged\Tests\TestCase;

final class GetInTouchTest extends TestCase {

    function testTypeReturnsGetInTouch (): void {
        $this->assertSame('get-in-touch', GetInTouchSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = GetInTouchSection::fromArray([
            'heading' => 'Your next project starts here.',
            'ctaText' => 'Get In Touch',
            'ctaHref' => 'mailto:hello@thesaiged.com',
        ]);

        $this->assertSame('Your next project starts here.', $section->heading);
        $this->assertSame('Get In Touch',                   $section->ctaText);
        $this->assertSame('mailto:hello@thesaiged.com',     $section->ctaHref);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['ctaText' => 't', 'ctaHref' => 'h']])]
    #[TestWith([['heading' => 'h', 'ctaHref' => 'h']])]
    #[TestWith([['heading' => 'h', 'ctaText' => 't']])]
    #[TestWith([['heading' => 1,   'ctaText' => 't', 'ctaHref' => 'h']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        GetInTouchSection::fromArray($data);
    }

    function testRenderEscapesContent (): void {
        $section = new GetInTouchSection(
            heading: 'A <b>bold</b> project',
            ctaText: 'Say "hello"',
            ctaHref: 'mailto:hello@example.com',
        );

        $html = $section->render();

        $this->assertStringContainsString('A &lt;b&gt;bold&lt;/b&gt; project', $html);
        $this->assertStringContainsString('Say &quot;hello&quot;',             $html);
        $this->assertStringContainsString('mailto:hello@example.com',          $html);
    }

    function testRenderContainsCssClasses (): void {
        $section = new GetInTouchSection('Heading', 'CTA', '/contact');
        $html    = $section->render();

        $this->assertStringContainsString('contact-footer',             $html);
        $this->assertStringContainsString('contact-footer-header',      $html);
        $this->assertStringContainsString('contact-footer-title',       $html);
        $this->assertStringContainsString('contact-footer-cta',         $html);
        $this->assertStringContainsString('--standalone',               $html);
    }

}
