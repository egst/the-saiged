<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ArtistGrid\ArtistGridSection;
use TheSaiged\Tests\TestCase;

final class ArtistGridTest extends TestCase {

    private function validData (): array {
        return [
            'heading' => 'Artists Recently Represented',
            'items'   => [
                ['uploadId' => 1, 'name' => 'Nicolas Party',  'birthYear' => '1980'],
                ['uploadId' => 2, 'name' => 'George Condo',   'birthYear' => '1957'],
                ['uploadId' => null, 'name' => 'Mark Rothko', 'birthYear' => '1903'],
            ],
        ];
    }

    function testTypeReturnsArtistGrid (): void {
        $this->assertSame('artist-grid', ArtistGridSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ArtistGridSection::fromArray($this->validData());

        $this->assertSame('Artists Recently Represented', $section->heading);
        $this->assertCount(3, $section->items);
        $this->assertSame('Nicolas Party', $section->items[0]['name']);
        $this->assertSame(1,               $section->items[0]['uploadId']);
        $this->assertNull(                 $section->items[2]['uploadId']);
    }

    function testFromArrayAcceptsEmptyItems (): void {
        $section = ArtistGridSection::fromArray(['heading' => 'H', 'items' => []]);

        $this->assertCount(0, $section->items);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['items' => []]])]
    #[TestWith([['heading' => 'H', 'items' => [['name' => 'N', 'birthYear' => '1980']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['uploadId' => 1, 'birthYear' => '1980']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['uploadId' => 1, 'name' => 'N']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['uploadId' => 'bad', 'name' => 'N', 'birthYear' => '1980']]]])]
    #[TestWith([['heading' => 'H', 'items' => ['not-array']]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ArtistGridSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = $this->validData();
        $section = ArtistGridSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new ArtistGridSection('<h>', [
            ['uploadId' => null, 'name' => '<n>', 'birthYear' => '<y>'],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('&lt;h&gt;', $html);
        $this->assertStringContainsString('&lt;n&gt;', $html);
        $this->assertStringContainsString('&lt;y&gt;', $html);
        $this->assertStringNotContainsString('<n>', $html);
    }

    function testRenderUsesVariantUrl (): void {
        $section = new ArtistGridSection('H', [
            ['uploadId' => 7, 'name' => 'N', 'birthYear' => '1980'],
        ]);

        $w    = ArtistGridSection::VARIANT_WIDTH;
        $h    = ArtistGridSection::VARIANT_HEIGHT;
        $this->assertStringContainsString("/uploads/7/{$w}x{$h}-cover.webp", $section->render());
    }

    function testRenderOmitsBirthYearWhenEmpty (): void {
        $section = new ArtistGridSection('H', [
            ['uploadId' => null, 'name' => 'N', 'birthYear' => ''],
        ]);

        $this->assertStringNotContainsString('artist-grid-birth', $section->render());
    }

    function testRenderProducesItemPerEntry (): void {
        $section = ArtistGridSection::fromArray($this->validData());

        $this->assertSame(3, substr_count($section->render(), 'artist-grid-name'));
    }

    function testRenderEmptyUploadIdProducesEmptyPlaceholder (): void {
        $section = new ArtistGridSection('H', [
            ['uploadId' => null, 'name' => 'N', 'birthYear' => '1980'],
        ]);

        $this->assertStringContainsString('artist-grid-image--empty', $section->render());
    }

}
