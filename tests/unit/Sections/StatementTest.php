<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Statement\StatementSection;
use TheSaiged\Tests\TestCase;

final class StatementTest extends TestCase {

    function testTypeReturnsStatement (): void {
        $this->assertSame('statement', StatementSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = StatementSection::fromArray([
            'heading'    => 'Where art meets strategy.',
            'body'       => 'A cultural studio.',
            'buttonText' => 'Explore Services',
            'buttonHref' => '/services',
        ]);

        $this->assertSame('Where art meets strategy.', $section->heading);
        $this->assertSame('A cultural studio.',        $section->body);
        $this->assertSame('Explore Services',          $section->buttonText);
        $this->assertSame('/services',                 $section->buttonHref);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([['body' => 'b', 'buttonText' => '', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'buttonText' => '', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'body' => 'b', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'body' => 'b', 'buttonText' => '']])]
    #[TestWith([['heading' => 1, 'body' => 'b', 'buttonText' => '', 'buttonHref' => '']])]
    #[TestWith([['heading' => 'h', 'body' => 'b', 'buttonText' => '', 'buttonHref' => 2]])]
    #[TestWith([[]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        StatementSection::fromArray($data);
    }

    function testRenderEscapesContent (): void {
        $section = new StatementSection('<h>', '<b>', 'Click', '/x');

        $html = $section->render();

        $this->assertStringContainsString('&lt;h&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<h>', $html);
    }

    function testRenderOmitsButtonWhenTextEmpty (): void {
        $section = new StatementSection('H', 'B', '', '/services');

        $this->assertStringNotContainsString('statement-button', $section->render());
    }

    function testRenderIncludesButtonWhenTextPresent (): void {
        $section = new StatementSection('H', 'B', 'Explore', '/services');

        $html = $section->render();

        $this->assertStringContainsString('statement-button', $html);
        $this->assertStringContainsString('href="/services"', $html);
        $this->assertStringContainsString('Explore', $html);
    }

}
