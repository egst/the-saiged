<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\PageCover\PageCoverSection;
use TheSaiged\Tests\TestCase;

final class PageCoverTest extends TestCase {

    function testTypeReturnsPageCover (): void {
        $this->assertSame('page-cover', PageCoverSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = PageCoverSection::fromArray([
            'uploadId' => 3,
            'eyebrow'  => 'About The Saiged',
            'heading'  => 'Cultural studio devoted to contemporary value',
        ]);

        $this->assertSame(3,                                                   $section->uploadId);
        $this->assertSame('About The Saiged',                                  $section->eyebrow);
        $this->assertSame('Cultural studio devoted to contemporary value',     $section->heading);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['eyebrow' => 'e', 'heading' => 'h']])]
    #[TestWith([['uploadId' => 3,   'heading' => 'h']])]
    #[TestWith([['uploadId' => 3,   'eyebrow' => 'e']])]
    #[TestWith([['uploadId' => '3', 'eyebrow' => 'e', 'heading' => 'h']])]
    #[TestWith([['uploadId' => 3,   'eyebrow' => 1,   'heading' => 'h']])]
    #[TestWith([[]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        PageCoverSection::fromArray($data);
    }

    function testToArrayRoundtripsThroughFromArray (): void {
        $original = new PageCoverSection(uploadId: 5, eyebrow: 'Eyebrow', heading: 'Heading');
        $clone    = PageCoverSection::fromArray($original->toArray());

        $this->assertSame($original->uploadId, $clone->uploadId);
        $this->assertSame($original->eyebrow,  $clone->eyebrow);
        $this->assertSame($original->heading,  $clone->heading);
    }

    function testRenderEmitsPredictableVariantUrl (): void {
        $section = new PageCoverSection(uploadId: 7, eyebrow: 'e', heading: 'h');

        $this->assertStringContainsString('/uploads/7/1920x1080-cover.webp', $section->render());
    }

    function testRenderEscapesEyebrow (): void {
        $section = new PageCoverSection(uploadId: 1, eyebrow: '<b>x</b>', heading: 'h');

        $html = $section->render();

        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    function testRenderEscapesHeading (): void {
        $section = new PageCoverSection(uploadId: 1, eyebrow: 'e', heading: '<script>alert(1)</script>');

        $html = $section->render();

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    function testRenderIncludesEyebrowAndHeading (): void {
        $section = new PageCoverSection(uploadId: 1, eyebrow: 'About', heading: 'Cultural Studio');

        $html = $section->render();

        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Cultural Studio', $html);
    }

}
