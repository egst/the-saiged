<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\LinkCarousel\LinkCarouselItem;
use TheSaiged\Sections\LinkCarousel\LinkCarouselSection;
use TheSaiged\Tests\TestCase;

final class LinkCarouselTest extends TestCase {

    function testTypeReturnsLinkCarousel (): void {
        $this->assertSame('link-carousel', LinkCarouselSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = LinkCarouselSection::fromArray([
            'items' => [
                ['uploadId' => 1, 'eyebrow' => 'Studio', 'title' => 'Project A', 'buttonText' => 'View', 'buttonHref' => '/a'],
                ['uploadId' => 2, 'eyebrow' => 'Work',   'title' => 'Project B', 'buttonText' => '',     'buttonHref' => ''],
            ],
        ]);

        $this->assertCount(2,          $section->items);
        $this->assertSame(1,           $section->items[0]->uploadId);
        $this->assertSame('Studio',    $section->items[0]->eyebrow);
        $this->assertSame('Project A', $section->items[0]->title);
        $this->assertSame('View',      $section->items[0]->buttonText);
        $this->assertSame('/a',        $section->items[0]->buttonHref);
        $this->assertSame(2,           $section->items[1]->uploadId);
    }

    function testFromArrayAcceptsEmptyItemsList (): void {
        $section = LinkCarouselSection::fromArray(['items' => []]);

        $this->assertSame([], $section->items);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['items' => 'not-a-list']])]
    #[TestWith([['items' => [['uploadId' => 0,   'eyebrow' => '', 'title' => '', 'buttonText' => '', 'buttonHref' => '']]]])]
    #[TestWith([['items' => [['uploadId' => -1,  'eyebrow' => '', 'title' => '', 'buttonText' => '', 'buttonHref' => '']]]])]
    #[TestWith([['items' => [['uploadId' => '1', 'eyebrow' => '', 'title' => '', 'buttonText' => '', 'buttonHref' => '']]]])]
    #[TestWith([['items' => [['uploadId' => 1,   'title' => '', 'buttonText' => '', 'buttonHref' => '']]]])]
    #[TestWith([['items' => [['uploadId' => 1,   'eyebrow' => '', 'buttonText' => '', 'buttonHref' => '']]]])]
    #[TestWith([['items' => [['uploadId' => 1,   'eyebrow' => '', 'title' => '', 'buttonHref' => '']]]])]
    #[TestWith([['items' => [['uploadId' => 1,   'eyebrow' => '', 'title' => '', 'buttonText' => '']]]])]
    #[TestWith([['items' => ['not-an-object']]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        LinkCarouselSection::fromArray($data);
    }

    function testToArrayRoundtripsThroughFromArray (): void {
        $original = new LinkCarouselSection([
            new LinkCarouselItem(uploadId: 3, eyebrow: 'E', title: 'T', buttonText: 'B', buttonHref: '/b'),
        ]);
        $clone = LinkCarouselSection::fromArray($original->toArray());

        $this->assertCount(1,    $clone->items);
        $this->assertSame(3,     $clone->items[0]->uploadId);
        $this->assertSame('E',   $clone->items[0]->eyebrow);
        $this->assertSame('T',   $clone->items[0]->title);
        $this->assertSame('B',   $clone->items[0]->buttonText);
        $this->assertSame('/b',  $clone->items[0]->buttonHref);
    }

    function testRenderEmitsPredictableVariantUrls (): void {
        $section = new LinkCarouselSection([
            new LinkCarouselItem(uploadId: 7, eyebrow: 'E', title: 'T', buttonText: 'B', buttonHref: '/x'),
        ]);

        $this->assertStringContainsString('/uploads/7/1920x1080-cover.webp', $section->render());
    }

    function testRenderEscapesContent (): void {
        $section = new LinkCarouselSection([
            new LinkCarouselItem(uploadId: 1, eyebrow: '<e>', title: '<t>', buttonText: '<b>', buttonHref: '/x'),
        ]);

        $html = $section->render();

        $this->assertStringContainsString('&lt;e&gt;', $html);
        $this->assertStringContainsString('&lt;t&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<e>', $html);
    }

    function testRenderOmitsButtonWhenTextEmpty (): void {
        $section = new LinkCarouselSection([
            new LinkCarouselItem(uploadId: 1, eyebrow: 'E', title: 'T', buttonText: '', buttonHref: '/x'),
        ]);

        $this->assertStringContainsString('hidden', $section->render());
    }

    function testRenderIncludesButtonWhenTextPresent (): void {
        $section = new LinkCarouselSection([
            new LinkCarouselItem(uploadId: 1, eyebrow: 'E', title: 'T', buttonText: 'Go', buttonHref: '/x'),
        ]);

        $html = $section->render();

        $this->assertStringContainsString('link-carousel-button', $html);
        $this->assertStringContainsString('href="/x"', $html);
        $this->assertStringContainsString('Go', $html);
        $this->assertStringNotContainsString('link-carousel-button" hidden', $html);
    }

    function testRenderReturnsEmptySectionForNoItems (): void {
        $section = new LinkCarouselSection([]);

        $this->assertStringContainsString('link-carousel', $section->render());
    }

    function testRenderFirstSlideIsActive (): void {
        $section = new LinkCarouselSection([
            new LinkCarouselItem(uploadId: 1, eyebrow: 'E', title: 'T', buttonText: '', buttonHref: ''),
            new LinkCarouselItem(uploadId: 2, eyebrow: 'E', title: 'T', buttonText: '', buttonHref: ''),
        ]);

        $html    = $section->render();
        $firstPos  = strpos($html, 'is-active');
        $secondPos = strpos($html, '/uploads/2/');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertLessThan($secondPos, $firstPos);
    }

}
