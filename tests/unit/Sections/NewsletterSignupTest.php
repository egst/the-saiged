<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\NewsletterSignup\NewsletterSignupSection;
use TheSaiged\Tests\TestCase;

final class NewsletterSignupTest extends TestCase {

    function testTypeReturnsNewsletterSignup (): void {
        $this->assertSame('newsletter-signup', NewsletterSignupSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = NewsletterSignupSection::fromArray([
            'heading'    => 'Interested in Artnet?',
            'body'       => 'Receive alerts on upcoming exhibitions.',
            'formAction' => 'https://example.list-manage.com/subscribe/post',
        ]);

        $this->assertSame('Interested in Artnet?',                        $section->heading);
        $this->assertSame('Receive alerts on upcoming exhibitions.',       $section->body);
        $this->assertSame('https://example.list-manage.com/subscribe/post', $section->formAction);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['body' => 'B', 'formAction' => 'U']])]
    #[TestWith([['heading' => 'H', 'formAction' => 'U']])]
    #[TestWith([['heading' => 'H', 'body' => 'B']])]
    #[TestWith([['heading' => 1, 'body' => 'B', 'formAction' => 'U']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        NewsletterSignupSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = ['heading' => 'H', 'body' => 'B', 'formAction' => 'https://u.com'];
        $section = NewsletterSignupSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new NewsletterSignupSection('<h>', '<b>', 'https://safe.com');
        $html    = $section->render();

        $this->assertStringNotContainsString('<h>', $html);
        $this->assertStringContainsString('&lt;h&gt;', $html);
    }

    function testRenderEscapesFormAction (): void {
        $section = new NewsletterSignupSection('H', 'B', 'https://x.com?a=1&b=2');
        $html    = $section->render();

        $this->assertStringNotContainsString('a=1&b=2', $html);
        $this->assertStringContainsString('a=1&amp;b=2', $html);
    }

    function testRenderOmitsBodyWhenEmpty (): void {
        $section = new NewsletterSignupSection('Heading', '', 'https://x.com');

        $this->assertStringNotContainsString('newsletter-body', $section->render());
    }

    function testRenderContainsEmailInput (): void {
        $section = new NewsletterSignupSection('H', 'B', 'https://x.com');

        $this->assertStringContainsString('type="email"', $section->render());
        $this->assertStringContainsString('name="EMAIL"', $section->render());
    }

}
