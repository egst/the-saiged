<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\SideList\SideListSection;
use TheSaiged\Tests\TestCase;

final class SideListTest extends TestCase {

    private function validData (): array {
        return [
            'heading'      => 'The Saiged Advisory',
            'body'         => 'First paragraph.\n\nSecond paragraph.',
            'linkText'     => 'Start a Conversation →',
            'linkHref'     => 'mailto:hello@thesaiged.com',
            'panelHeading' => 'Integrated Approach',
            'items'        => [
                ['title' => 'Studio',   'body' => 'Brand partnerships.'],
                ['title' => 'Advisory', 'body' => 'Private art sales.'],
            ],
        ];
    }

    function testTypeReturnsSideList (): void {
        $this->assertSame('side-list', SideListSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = SideListSection::fromArray($this->validData());

        $this->assertSame('The Saiged Advisory',      $section->heading);
        $this->assertSame('Integrated Approach',       $section->panelHeading);
        $this->assertSame('Start a Conversation →',   $section->linkText);
        $this->assertCount(2, $section->items);
        $this->assertSame('Studio', $section->items[0]['title']);
    }

    function testFromArrayAcceptsEmptyItems (): void {
        $data           = $this->validData();
        $data['items']  = [];
        $section        = SideListSection::fromArray($data);

        $this->assertCount(0, $section->items);
    }

    function testFromArrayAcceptsEmptyLink (): void {
        $data             = $this->validData();
        $data['linkText'] = '';
        $data['linkHref'] = '';

        $section = SideListSection::fromArray($data);

        $this->assertSame('', $section->linkText);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['heading' => 'H', 'body' => 'B', 'linkText' => 'L', 'linkHref' => 'U', 'panelHeading' => 'P']])]
    #[TestWith([['heading' => 1,   'body' => 'B', 'linkText' => 'L', 'linkHref' => 'U', 'panelHeading' => 'P', 'items' => []]])]
    #[TestWith([['heading' => 'H', 'body' => 'B', 'linkText' => 'L', 'linkHref' => 'U', 'panelHeading' => 'P', 'items' => [['title' => 'T']]]])]
    #[TestWith([['heading' => 'H', 'body' => 'B', 'linkText' => 'L', 'linkHref' => 'U', 'panelHeading' => 'P', 'items' => [['body' => 'B']]]])]
    #[TestWith([['heading' => 'H', 'body' => 'B', 'linkText' => 'L', 'linkHref' => 'U', 'panelHeading' => 'P', 'items' => ['not-array']]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        SideListSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = $this->validData();
        $section = SideListSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new SideListSection('<h>', '<b>', '<lt>', '<lh>', '<ph>', [
            ['title' => '<t>', 'body' => '<ib>'],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('&lt;h&gt;',  $html);
        $this->assertStringContainsString('&lt;b&gt;',  $html);
        $this->assertStringContainsString('&lt;t&gt;',  $html);
        $this->assertStringContainsString('&lt;ib&gt;', $html);
        $this->assertStringNotContainsString('<h>', $html);
    }

    function testRenderSplitsBodyOnDoubleNewline (): void {
        $section = new SideListSection('H', "First paragraph.\n\nSecond paragraph.", '', '', 'P', []);

        $html = $section->render();

        $this->assertSame(2, substr_count($html, 'side-list-body-para'));
        $this->assertStringContainsString('First paragraph.', $html);
        $this->assertStringContainsString('Second paragraph.', $html);
    }

    function testRenderOmitsLinkWhenEmpty (): void {
        $section = new SideListSection('H', 'B', '', '', 'P', []);

        $this->assertStringNotContainsString('side-list-link', $section->render());
    }

    function testRenderIncludesLinkWhenPresent (): void {
        $section = new SideListSection('H', 'B', 'Enquire', 'mailto:hello@example.com', 'P', []);

        $html = $section->render();

        $this->assertStringContainsString('side-list-link', $html);
        $this->assertStringContainsString('href="mailto:hello@example.com"', $html);
        $this->assertStringContainsString('Enquire', $html);
    }

    function testRenderProducesItemPerEntry (): void {
        $section = new SideListSection('H', 'B', '', '', 'P', [
            ['title' => 'A', 'body' => 'a'],
            ['title' => 'B', 'body' => 'b'],
            ['title' => 'C', 'body' => 'c'],
        ]);

        $this->assertSame(3, substr_count($section->render(), 'side-list-item-title'));
    }

}
