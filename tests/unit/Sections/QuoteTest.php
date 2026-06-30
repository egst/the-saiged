<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Quote\QuoteSection;
use TheSaiged\Tests\TestCase;

final class QuoteTest extends TestCase {

    function testTypeReturnsQuote (): void {
        $this->assertSame('quote', QuoteSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = QuoteSection::fromArray([
            'quote' => '"The platforms that will matter..."',
            'cite'  => 'Sophie Neuendorf',
        ]);

        $this->assertSame('"The platforms that will matter..."', $section->quote);
        $this->assertSame('Sophie Neuendorf', $section->cite);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['cite' => 'A']])]
    #[TestWith([['quote' => 'Q']])]
    #[TestWith([['quote' => 1, 'cite' => 'A']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        QuoteSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = ['quote' => 'Q', 'cite' => 'C'];
        $section = QuoteSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new QuoteSection('<q>', '<c>');
        $html    = $section->render();

        $this->assertStringContainsString('&lt;q&gt;', $html);
        $this->assertStringContainsString('&lt;c&gt;', $html);
        $this->assertStringNotContainsString('<q>', $html);
    }

    function testRenderOmitsCiteWhenEmpty (): void {
        $section = new QuoteSection('Quote text', '');

        $this->assertStringNotContainsString('quote-cite', $section->render());
    }

    function testRenderIncludesCiteWhenPresent (): void {
        $section = new QuoteSection('Q', 'Author Name');

        $this->assertStringContainsString('quote-cite', $section->render());
        $this->assertStringContainsString('Author Name', $section->render());
    }

}
