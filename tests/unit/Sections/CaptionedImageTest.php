<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\CaptionedImage\CaptionedImageSection;
use TheSaiged\Tests\TestCase;

final class CaptionedImageTest extends TestCase {

    function testTypeReturnsCaptionedImage (): void {
        $this->assertSame('captioned-image', CaptionedImageSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = CaptionedImageSection::fromArray([
            'uploadId' => 42,
            'caption'  => 'Artnet interface',
        ]);

        $this->assertSame(42, $section->uploadId);
        $this->assertSame('Artnet interface', $section->caption);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['caption' => 'C']])]
    #[TestWith([['uploadId' => 1]])]
    #[TestWith([['uploadId' => 'bad', 'caption' => 'C']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        CaptionedImageSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = ['uploadId' => 7, 'caption' => 'C'];
        $section = CaptionedImageSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderContainsVariantUrl (): void {
        $section = new CaptionedImageSection(5, 'My caption');
        $html    = $section->render();

        $this->assertStringContainsString('/uploads/5/1920x1280-cover.webp', $html);
    }

    function testRenderEscapesCaption (): void {
        $section = new CaptionedImageSection(1, '<script>');
        $html    = $section->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    function testRenderOmitsFigcaptionWhenEmpty (): void {
        $section = new CaptionedImageSection(1, '');

        $this->assertStringNotContainsString('figcaption', $section->render());
    }

}
