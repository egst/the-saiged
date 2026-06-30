<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ArticleCover\ArticleCoverSection;
use TheSaiged\Tests\TestCase;

final class ArticleCoverTest extends TestCase {

    function testTypeReturnsArticleCover (): void {
        $this->assertSame('article-cover', ArticleCoverSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ArticleCoverSection::fromArray([
            'uploadId' => 1,
            'eyebrow'  => 'Exclusive Interview',
            'heading'  => 'Sophie Neuendorf about Artnet',
            'body'     => 'A conversation on art-world infrastructure.',
        ]);

        $this->assertSame(1,                                        $section->uploadId);
        $this->assertSame('Exclusive Interview',                    $section->eyebrow);
        $this->assertSame('Sophie Neuendorf about Artnet',          $section->heading);
        $this->assertSame('A conversation on art-world infrastructure.', $section->body);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['eyebrow' => 'E', 'heading' => 'H', 'body' => 'B']])]
    #[TestWith([['uploadId' => 1, 'heading' => 'H', 'body' => 'B']])]
    #[TestWith([['uploadId' => 1, 'eyebrow' => 'E', 'body' => 'B']])]
    #[TestWith([['uploadId' => 1, 'eyebrow' => 'E', 'heading' => 'H']])]
    #[TestWith([['uploadId' => 'bad', 'eyebrow' => 'E', 'heading' => 'H', 'body' => 'B']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ArticleCoverSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = ['uploadId' => 3, 'eyebrow' => 'E', 'heading' => 'H', 'body' => 'B'];
        $section = ArticleCoverSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderContainsVariantUrl (): void {
        $section = new ArticleCoverSection(9, 'E', 'H', 'B');

        $this->assertStringContainsString('/uploads/9/1920x1080-cover.webp', $section->render());
    }

    function testRenderEscapesContent (): void {
        $section = new ArticleCoverSection(1, '<e>', '<h>', '<b>');
        $html    = $section->render();

        $this->assertStringNotContainsString('<e>', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;e&gt;', $html);
    }

    function testRenderOmitsEyebrowWhenEmpty (): void {
        $section = new ArticleCoverSection(1, '', 'Heading', 'Body');

        $this->assertStringNotContainsString('article-cover-eyebrow', $section->render());
    }

    function testRenderOmitsBodyWhenEmpty (): void {
        $section = new ArticleCoverSection(1, 'Eyebrow', 'Heading', '');

        $this->assertStringNotContainsString('article-cover-body', $section->render());
    }

}
