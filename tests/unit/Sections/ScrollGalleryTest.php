<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ScrollGallery\ScrollGallerySection;
use TheSaiged\Tests\TestCase;

final class ScrollGalleryTest extends TestCase {

    function testTypeReturnsScrollGallery (): void {
        $this->assertSame('scroll-gallery', ScrollGallerySection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ScrollGallerySection::fromArray([
            'items' => [
                ['uploadId' => 1, 'caption' => 'First'],
                ['uploadId' => 2, 'caption' => ''],
            ],
        ]);

        $this->assertCount(2, $section->items);
        $this->assertSame(1,       $section->items[0]['uploadId']);
        $this->assertSame('First', $section->items[0]['caption']);
    }

    function testFromArrayAcceptsEmptyItems (): void {
        $section = ScrollGallerySection::fromArray(['items' => []]);

        $this->assertCount(0, $section->items);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['items' => 'not-array']])]
    #[TestWith([['items' => [['caption' => 'C']]]])]
    #[TestWith([['items' => [['uploadId' => 1]]]])]
    #[TestWith([['items' => [['uploadId' => 'bad', 'caption' => 'C']]]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ScrollGallerySection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = ['items' => [['uploadId' => 3, 'caption' => 'Cap']]];
        $section = ScrollGallerySection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderContainsVariantUrls (): void {
        $section = new ScrollGallerySection([
            ['uploadId' => 5, 'caption' => 'C'],
        ]);
        $html = $section->render();

        $this->assertStringContainsString('/uploads/5/680x800-cover.webp', $html);
    }

    function testRenderEscapesCaption (): void {
        $section = new ScrollGallerySection([['uploadId' => 1, 'caption' => '<script>']]);
        $html    = $section->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    function testRenderOmitsCaptionDivWhenEmpty (): void {
        $section = new ScrollGallerySection([['uploadId' => 1, 'caption' => '']]);

        $this->assertStringNotContainsString('scroll-gallery-caption', $section->render());
    }

}
