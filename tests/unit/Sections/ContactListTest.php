<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\ContactList\ContactListSection;
use TheSaiged\Tests\TestCase;

final class ContactListTest extends TestCase {

    function testTypeReturnsContactList (): void {
        $this->assertSame('contact-list', ContactListSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = ContactListSection::fromArray([
            'heading' => 'Contact Us',
            'items'   => [
                ['heading' => 'Studio', 'body' => 'Email studio@thesaiged.com', 'note' => ''],
                ['heading' => 'Advisory', 'body' => 'Email advisory@thesaiged.com', 'note' => 'Prague / London'],
            ],
        ]);

        $this->assertSame('Contact Us', $section->heading);
        $this->assertCount(2, $section->items);
        $this->assertSame('Studio',          $section->items[0]['heading']);
        $this->assertSame('Advisory',        $section->items[1]['heading']);
        $this->assertSame('Prague / London', $section->items[1]['note']);
    }

    function testFromArrayAcceptsEmptyItems (): void {
        $section = ContactListSection::fromArray(['heading' => 'Contact', 'items' => []]);

        $this->assertCount(0, $section->items);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['items' => 'not-an-array']])]
    #[TestWith([['heading' => 'H', 'items' => [['body' => 'b', 'note' => '']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['heading' => 'h', 'note' => '']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['heading' => 'h', 'body' => 'b']]]])]
    #[TestWith([['heading' => 'H', 'items' => [['heading' => 1, 'body' => 'b', 'note' => '']]]])]
    #[TestWith([['heading' => 'H', 'items' => ['not-an-array']]])]
    #[TestWith([['items' => []]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        ContactListSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = ['heading' => 'Contact Us', 'items' => [['heading' => 'H', 'body' => 'B', 'note' => 'N']]];
        $section = ContactListSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new ContactListSection('<title>', [
            ['heading' => '<h>', 'body' => '<b>', 'note' => '<n>'],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('&lt;title&gt;', $html);
        $this->assertStringContainsString('&lt;h&gt;',     $html);
        $this->assertStringContainsString('&lt;b&gt;',     $html);
        $this->assertStringContainsString('&lt;n&gt;',     $html);
        $this->assertStringNotContainsString('<h>', $html);
    }

    function testRenderLinksEmailAddresses (): void {
        $section = new ContactListSection('H', [
            ['heading' => 'Studio', 'body' => 'Contact studio@thesaiged.com for details.', 'note' => ''],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('href="mailto:studio@thesaiged.com"', $html);
        $this->assertStringContainsString('>studio@thesaiged.com<', $html);
    }

    function testRenderOmitsNoteWhenEmpty (): void {
        $section = new ContactListSection('H', [
            ['heading' => 'H', 'body' => 'B', 'note' => ''],
        ]);

        $this->assertStringNotContainsString('contact-list-note', $section->render());
    }

    function testRenderIncludesNoteWhenPresent (): void {
        $section = new ContactListSection('H', [
            ['heading' => 'H', 'body' => 'B', 'note' => 'Prague / London'],
        ]);

        $html = $section->render();

        $this->assertStringContainsString('contact-list-note', $html);
        $this->assertStringContainsString('Prague / London', $html);
    }

    function testRenderProducesItemPerEntry (): void {
        $section = new ContactListSection('H', [
            ['heading' => 'A', 'body' => 'a', 'note' => ''],
            ['heading' => 'B', 'body' => 'b', 'note' => ''],
        ]);

        $this->assertSame(2, substr_count($section->render(), 'contact-list-heading'));
    }

}
