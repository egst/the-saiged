<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell\Header;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Header\HeaderShell;
use TheSaiged\Tests\TestCase;

final class HeaderShellTest extends TestCase {

    function testTypeIsHeader (): void {
        $this->assertSame('header', HeaderShell::type());
    }

    function testDefaultHasNoLinksAndNoLogo (): void {
        $header = HeaderShell::default();

        $this->assertSame([], $header->links);
        $this->assertNull($header->logoUploadId);
    }

    function testFromArrayParsesLinksAndLogo (): void {
        $header = HeaderShell::fromArray([
            'links' => [
                ['label' => 'Studio', 'href' => '/studio'],
                ['label' => 'Features', 'href' => '/features'],
            ],
            'logoUploadId' => 7,
        ]);

        $this->assertCount(2, $header->links);
        $this->assertSame('Studio', $header->links[0]->label);
        $this->assertSame('/features', $header->links[1]->href);
        $this->assertSame(7, $header->logoUploadId);
    }

    function testFromArrayAllowsNullLogo (): void {
        $header = HeaderShell::fromArray(['links' => [], 'logoUploadId' => null]);

        $this->assertNull($header->logoUploadId);
    }

    function testToArrayIsInverseOfFromArray (): void {
        $data = ['links' => [['label' => 'Studio', 'href' => '/studio']], 'logoUploadId' => 3];

        $this->assertSame($data, HeaderShell::fromArray($data)->toArray());
    }

    #[TestWith([['logoUploadId' => null]])]
    #[TestWith([['links' => 'not-a-list', 'logoUploadId' => null]])]
    #[TestWith([['links' => [['label' => 'Studio']], 'logoUploadId' => null]])]
    #[TestWith([['links' => [], 'logoUploadId' => 'not-an-int']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        HeaderShell::fromArray($data);
    }

    function testRenderIncludesEscapedLinksAndSearchButton (): void {
        $header = HeaderShell::fromArray([
            'links' => [['label' => 'A & B', 'href' => '/a-and-b']],
            'logoUploadId' => null,
        ]);

        $html = $header->render();

        $this->assertStringContainsString('<header class="site-header">', $html);
        $this->assertStringContainsString('A &amp; B',                    $html);
        $this->assertStringContainsString('href="/a-and-b"',              $html);
        $this->assertStringContainsString('header-search-bar',            $html);
    }

    function testRenderIncludesScrollProgressBar (): void {
        $html = HeaderShell::default()->render();

        $this->assertStringContainsString('class="scroll-progress"',     $html);
        $this->assertStringContainsString('class="scroll-progress-bar"', $html);
    }

    function testRenderFallsBackToTextLogoWhenUnset (): void {
        $html = HeaderShell::default()->render();

        $this->assertStringContainsString('The Saiged', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    function testRenderUsesPredictableVariantUrlForLogo (): void {
        $header = HeaderShell::fromArray(['links' => [], 'logoUploadId' => 9]);

        $html = $header->render();

        $this->assertStringContainsString('/uploads/images/9/240x80-cover.webp', $html);
    }

    function testCssAssetsAndJsAssets (): void {
        $this->assertSame(['style.css'], HeaderShell::cssAssets());
        $this->assertSame(['header.js'], HeaderShell::jsAssets());
    }

}
