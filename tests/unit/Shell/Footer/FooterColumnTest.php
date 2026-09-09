<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell\Footer;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Footer\FooterColumn;
use TheSaiged\Tests\TestCase;

final class FooterColumnTest extends TestCase {

    function testFromArrayParsesHeadingAndMixedItemKinds (): void {
        $column = FooterColumn::fromArray([
            'heading' => 'SOCIAL',
            'items'   => [
                ['kind' => 'link', 'label' => 'Instagram', 'href' => 'https://instagram.com'],
                ['kind' => 'text', 'content' => 'Prague / London'],
                ['kind' => 'newsletter'],
            ],
        ]);

        $this->assertSame('SOCIAL', $column->heading);
        $this->assertCount(3, $column->items);
    }

    function testToArrayIsInverseOfFromArray (): void {
        $data = [
            'heading' => 'LEGAL',
            'items'   => [['kind' => 'link', 'label' => 'Terms', 'href' => '/terms']],
        ];

        $this->assertSame($data, FooterColumn::fromArray($data)->toArray());
    }

    #[TestWith([['items' => []]])]
    #[TestWith([['heading' => 'X', 'items' => 'not-a-list']])]
    #[TestWith([['heading' => 'X', 'items' => [['kind' => 'unknown']]]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        FooterColumn::fromArray($data);
    }

    function testRenderIncludesHeadingAndEachItemsOwnMarkup (): void {
        $column = FooterColumn::fromArray([
            'heading' => 'SOCIAL',
            'items'   => [['kind' => 'link', 'label' => 'Instagram', 'href' => '/ig']],
        ]);

        $html = $column->render();

        $this->assertStringContainsString('<h4>SOCIAL</h4>',        $html);
        $this->assertStringContainsString('<a href="/ig">Instagram</a>', $html);
    }

    function testRenderOmitsHeadingTagWhenEmpty (): void {
        $column = FooterColumn::fromArray(['heading' => '', 'items' => []]);

        $this->assertStringNotContainsString('<h4>', $column->render());
    }

}
