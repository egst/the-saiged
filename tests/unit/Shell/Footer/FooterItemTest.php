<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell\Footer;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Footer\FooterItem;
use TheSaiged\Tests\TestCase;

final class FooterItemTest extends TestCase {

    function testLinkFromArrayRoundTrips (): void {
        $data = ['kind' => 'link', 'label' => 'Instagram', 'href' => 'https://instagram.com'];

        $this->assertSame($data, FooterItem::fromArray($data)->toArray());
    }

    function testTextFromArrayRoundTrips (): void {
        $data = ['kind' => 'text', 'content' => 'Prague / London'];

        $this->assertSame($data, FooterItem::fromArray($data)->toArray());
    }

    function testNewsletterFromArrayRoundTrips (): void {
        $data = ['kind' => 'newsletter'];

        $this->assertSame($data, FooterItem::fromArray($data)->toArray());
    }

    #[TestWith([[]])]
    #[TestWith([['kind' => 'unknown']])]
    #[TestWith([['kind' => 'link', 'href' => '/x']])]
    #[TestWith([['kind' => 'link', 'label' => 'X']])]
    #[TestWith([['kind' => 'text']])]
    #[TestWith([['kind' => 'text', 'content' => 123]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        FooterItem::fromArray($data);
    }

    function testRenderLinkEscapesLabelAndHref (): void {
        $item = FooterItem::link('A & B', '/a?x=1&y=2');

        $this->assertSame('<a href="/a?x=1&amp;y=2">A &amp; B</a>', $item->render());
    }

    function testRenderTextEscapesPlainContent (): void {
        $item = FooterItem::text('Terms & Conditions');

        $this->assertSame('<p>Terms &amp; Conditions</p>', $item->render());
    }

    function testRenderTextTurnsMarkdownLinkIntoAnchor (): void {
        $item = FooterItem::text('By continuing you agree to the [Privacy Policy](/privacy).');

        $html = $item->render();

        $this->assertSame(
            '<p>By continuing you agree to the <a href="/privacy">Privacy Policy</a>.</p>',
            $html,
        );
    }

    function testRenderTextEscapesHrefAndLabelInsideMarkdownLink (): void {
        $item = FooterItem::text('[A & B](/x?y=1&z=2)');

        $html = $item->render();

        $this->assertSame('<p><a href="/x?y=1&amp;z=2">A &amp; B</a></p>', $html);
    }

    function testRenderNewsletterProducesAnInertForm (): void {
        $html = FooterItem::newsletter()->render();

        $this->assertStringContainsString('<form class="footer-newsletter-form"', $html);
        $this->assertStringContainsString('onsubmit="return false;"',             $html);
        $this->assertStringContainsString('type="email"',                         $html);
    }

}
