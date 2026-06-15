<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\SplitBanner\SplitBannerSection;
use TheSaiged\Tests\TestCase;

final class SplitBannerTest extends TestCase {

    function testTypeReturnsSplitBanner (): void {
        $this->assertSame('split-banner', SplitBannerSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = SplitBannerSection::fromArray([
            'uploadId'   => 7,
            'label'      => 'Feature',
            'heading'    => 'Venice Biennale',
            'date'       => 'May 9 — Nov 22, 2026',
            'buttonText' => 'Read now',
            'buttonHref' => '/venice',
        ]);

        $this->assertSame(7,                    $section->uploadId);
        $this->assertSame('Feature',            $section->label);
        $this->assertSame('Venice Biennale',    $section->heading);
        $this->assertSame('May 9 — Nov 22, 2026', $section->date);
        $this->assertSame('Read now',           $section->buttonText);
        $this->assertSame('/venice',            $section->buttonHref);
    }

    function testFromArrayAcceptsNullUploadId (): void {
        $section = SplitBannerSection::fromArray([
            'uploadId'   => null,
            'label'      => '',
            'heading'    => 'H',
            'date'       => '',
            'buttonText' => '',
            'buttonHref' => '',
        ]);

        $this->assertNull($section->uploadId);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['uploadId' => null, 'label' => 'L', 'heading' => 'H', 'date' => 'D', 'buttonText' => 'B']])]
    #[TestWith([['uploadId' => null, 'label' => 'L', 'heading' => 'H', 'date' => 'D', 'buttonHref' => '/']])]
    #[TestWith([['uploadId' => 'x', 'label' => 'L', 'heading' => 'H', 'date' => '', 'buttonText' => '', 'buttonHref' => '']])]
    #[TestWith([['uploadId' => null, 'heading' => 'H', 'date' => '', 'buttonText' => '', 'buttonHref' => '']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        SplitBannerSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data = [
            'uploadId'   => 3,
            'label'      => 'Feature',
            'heading'    => 'H',
            'date'       => 'D',
            'buttonText' => 'B',
            'buttonHref' => '/',
        ];

        $this->assertSame($data, SplitBannerSection::fromArray($data)->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new SplitBannerSection(null, '<l>', '<h>', '<d>', '<bt>', '<bh>');

        $html = $section->render();

        $this->assertStringContainsString('&lt;l&gt;', $html);
        $this->assertStringContainsString('&lt;h&gt;', $html);
        $this->assertStringContainsString('&lt;d&gt;', $html);
        $this->assertStringContainsString('&lt;bt&gt;', $html);
        $this->assertStringNotContainsString('<l>', $html);
    }

    function testRenderOmitsLabelWhenEmpty (): void {
        $section = new SplitBannerSection(null, '', 'H', '', '', '');

        $this->assertStringNotContainsString('split-banner-label', $section->render());
    }

    function testRenderOmitsDateWhenEmpty (): void {
        $section = new SplitBannerSection(null, '', 'H', '', '', '');

        $this->assertStringNotContainsString('split-banner-date', $section->render());
    }

    function testRenderOmitsButtonWhenTextEmpty (): void {
        $section = new SplitBannerSection(null, '', 'H', '', '', '/link');

        $this->assertStringNotContainsString('split-banner-button', $section->render());
    }

    function testRenderIncludesButtonWhenTextPresent (): void {
        $section = new SplitBannerSection(null, '', 'H', '', 'Read now', '/link');

        $html = $section->render();

        $this->assertStringContainsString('split-banner-button', $html);
        $this->assertStringContainsString('href="/link"', $html);
    }

    function testRenderIncludesImageWhenUploadIdPresent (): void {
        $section = new SplitBannerSection(5, '', 'H', '', '', '');

        $html = $section->render();

        $this->assertStringContainsString('split-banner-image', $html);
        $this->assertStringContainsString('/uploads/5/', $html);
    }

    function testRenderOmitsImageWhenUploadIdNull (): void {
        $section = new SplitBannerSection(null, '', 'H', '', '', '');

        $this->assertStringNotContainsString('split-banner-image', $section->render());
    }

}
