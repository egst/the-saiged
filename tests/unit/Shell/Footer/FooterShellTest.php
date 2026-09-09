<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell\Footer;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Footer\FooterShell;
use TheSaiged\Tests\TestCase;

final class FooterShellTest extends TestCase {

    function testTypeIsFooter (): void {
        $this->assertSame('footer', FooterShell::type());
    }

    function testDefaultHasNoColumnsAndNoLogo (): void {
        $footer = FooterShell::default();

        $this->assertSame([], $footer->columns);
        $this->assertNull($footer->logoUploadId);
    }

    function testToArrayIsInverseOfFromArray (): void {
        $data = [
            'columns' => [
                ['heading' => 'SOCIAL', 'items' => [['kind' => 'link', 'label' => 'IG', 'href' => '/ig']]],
            ],
            'logoUploadId' => 12,
        ];

        $this->assertSame($data, FooterShell::fromArray($data)->toArray());
    }

    #[TestWith([['logoUploadId' => null]])]
    #[TestWith([['columns' => 'not-a-list', 'logoUploadId' => null]])]
    #[TestWith([['columns' => [], 'logoUploadId' => 'nope']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        FooterShell::fromArray($data);
    }

    function testRenderIncludesEachColumnAndLogo (): void {
        $footer = FooterShell::fromArray([
            'columns' => [['heading' => 'SOCIAL', 'items' => []]],
            'logoUploadId' => 4,
        ]);

        $html = $footer->render();

        $this->assertStringContainsString('<footer class="contact-footer">', $html);
        $this->assertStringContainsString('<h4>SOCIAL</h4>',                 $html);
        $this->assertStringContainsString('/uploads/4/480x160-cover.webp',   $html);
    }

    function testRenderOmitsLogoImageWhenUnset (): void {
        $html = FooterShell::default()->render();

        $this->assertStringNotContainsString('<img', $html);
    }

    function testCssAssetsAndJsAssets (): void {
        $this->assertSame(['style.css'], FooterShell::cssAssets());
        $this->assertSame([], FooterShell::jsAssets());
    }

}
