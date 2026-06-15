<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ImageBreak\ImageBreakSection;
use TheSaiged\Tests\TestCase;

final class ImageBreakTest extends TestCase {

    function testTypeReturnsImageBreak (): void {
        $this->assertSame('image-break', ImageBreakSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ImageBreakSection::fromArray([
            'uploadId' => 5,
            'caption'  => 'The Saiged / Cultural Studio',
        ]);

        $this->assertSame(5,                              $section->uploadId);
        $this->assertSame('The Saiged / Cultural Studio', $section->caption);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['caption' => 'c']])]
    #[TestWith([['uploadId' => 5]])]
    #[TestWith([['uploadId' => '5', 'caption' => 'c']])]
    #[TestWith([['uploadId' => 5, 'caption' => 9]])]
    #[TestWith([[]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ImageBreakSection::fromArray($data);
    }

    function testToArrayRoundtripsThroughFromArray (): void {
        $original = new ImageBreakSection(uploadId: 5, caption: 'Studio');
        $clone    = ImageBreakSection::fromArray($original->toArray());

        $this->assertSame($original->uploadId, $clone->uploadId);
        $this->assertSame($original->caption,  $clone->caption);
    }

    function testRenderEmitsPredictableVariantUrl (): void {
        $section = new ImageBreakSection(uploadId: 7, caption: 'Studio');

        $this->assertStringContainsString('/uploads/7/1920x1080-cover.webp', $section->render());
    }

    function testRenderEscapesCaption (): void {
        $section = new ImageBreakSection(uploadId: 1, caption: '<em>x</em>');

        $html = $section->render();

        $this->assertStringContainsString('&lt;em&gt;', $html);
        $this->assertStringNotContainsString('<em>', $html);
    }

    function testRenderIncludesCaption (): void {
        $section = new ImageBreakSection(uploadId: 1, caption: 'Cultural Studio');

        $this->assertStringContainsString('Cultural Studio', $section->render());
    }

}
