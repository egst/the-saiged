<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell;

use TheSaiged\Core\Container;
use TheSaiged\Pages\Page;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Shell\Footer\FooterShell;
use TheSaiged\Shell\Header\HeaderLink;
use TheSaiged\Shell\Header\HeaderShell;
use TheSaiged\Shell\Layout;
use TheSaiged\Shell\ShellService;
use TheSaiged\Tests\TestCase;
use TheSaiged\Typography\TypographyService;

/**
 * Layout composes header + page body + footer into the full document.
 * ShellService is mocked; each shell's own render() correctness is
 * covered by HeaderShellTest/FooterShellTest.
 */
final class LayoutTest extends TestCase {

    function testRenderOrdersHeaderBodyFooter (): void {
        $header = new HeaderShell(links: [new HeaderLink('Studio', '/studio')], logoUploadId: null);
        $footer = FooterShell::default();
        $this->mockShells($header, $footer);

        $page = new Page(1, 'about', 'About', null, PageStatus::Published, [new ArticleSection('Body content')]);
        $html = Container::get(Layout::class)->render($page);

        $headerPos = strpos($html, '<header class="site-header">');
        $bodyPos   = strpos($html, 'Body content');
        $footerPos = strpos($html, '<footer class="contact-footer">');

        $this->assertNotFalse($headerPos);
        $this->assertNotFalse($bodyPos);
        $this->assertNotFalse($footerPos);
        $this->assertTrue($headerPos < $bodyPos && $bodyPos < $footerPos);
    }

    function testRenderIncludesDoctypeTitleAndGlobalAssets (): void {
        $this->mockShells(HeaderShell::default(), FooterShell::default());

        $page = new Page(1, 'p', 'My Title', null, PageStatus::Published, []);
        $html = Container::get(Layout::class)->render($page);

        $this->assertStringContainsString('<!DOCTYPE html>',          $html);
        $this->assertStringContainsString('<title>My Title</title>',  $html);
        $this->assertStringContainsString('/css/public/main.css',     $html);
        $this->assertStringContainsString('/css/public/overlay.css',  $html);
        $this->assertStringContainsString('/js/main.js',              $html);
    }

    function testRenderIncludesShellCssAssetTags (): void {
        $this->mockShells(HeaderShell::default(), FooterShell::default());

        $html = Container::get(Layout::class)->render(new Page(1, 'p', 't', null, PageStatus::Published, []));

        $this->assertStringContainsString('/shell/Header/style.css', $html);
        $this->assertStringContainsString('/shell/Footer/style.css', $html);
    }

    function testRenderIncludesSectionAssetsAndMetaDescription (): void {
        $this->mockShells(HeaderShell::default(), FooterShell::default());

        $page = new Page(1, 'p', 't', 'A description.', PageStatus::Published, [new ArticleSection('x')]);
        $html = Container::get(Layout::class)->render($page);

        $this->assertStringContainsString('/sections/Article/style.css',        $html);
        $this->assertStringContainsString('name="description"',                 $html);
        $this->assertStringContainsString('A description.',                     $html);
    }

    function testRenderEmitsFontFaceAndOverrideWhenRoleHasFace (): void {
        $this->mockShells(HeaderShell::default(), FooterShell::default());
        $this->mockTypography([
            'heading' => [],
            'text'    => [[
                'weightMin' => 400,
                'weightMax' => 700,
                'style'     => 'normal',
                'upload'    => ['originalUrl' => '/uploads/fonts/9.woff2', 'filename' => 'brand.woff2'],
            ]],
        ]);

        $html = Container::get(Layout::class)->render(new Page(1, 'p', 't', null, PageStatus::Published, []));

        $this->assertStringContainsString("@font-face", $html);
        $this->assertStringContainsString("font-family: 'CustomTextFont';", $html);
        $this->assertStringContainsString("src: url('/uploads/fonts/9.woff2') format('woff2');", $html);
        $this->assertStringContainsString('font-weight: 400 700;', $html);
        $this->assertStringContainsString("--sans: 'CustomTextFont', Arial, Helvetica, sans-serif;", $html);
        $this->assertStringNotContainsString('CustomHeadingFont', $html);
    }

    function testRenderOmitsOverrideWhenNoRoleHasFaces (): void {
        $this->mockShells(HeaderShell::default(), FooterShell::default());

        $html = Container::get(Layout::class)->render(new Page(1, 'p', 't', null, PageStatus::Published, []));

        $this->assertStringNotContainsString('@font-face',       $html);
        $this->assertStringNotContainsString('CustomTextFont',    $html);
        $this->assertStringNotContainsString('CustomHeadingFont', $html);
    }

    /** @param array<string, list<array<string, mixed>>> $facesByRole */
    private function mockTypography (array $facesByRole): void {
        $typography = $this->createMock(TypographyService::class);
        $typography->method('get')->willReturn($facesByRole);
        Container::set(TypographyService::class, $typography);
    }

    private function mockShells (HeaderShell $header, FooterShell $footer): void {
        $service = $this->createMock(ShellService::class);
        $service->method('get')->willReturnMap([
            ['header', $header],
            ['footer', $footer],
        ]);
        Container::set(ShellService::class, $service);

        // No custom fonts assigned by default — Layout must fall back to
        // main.css's own Kalice/Arial declarations without touching them.
        // Typography-specific rendering (real faces, overrides) is covered
        // separately in testRenderTypography* below.
        $typography = $this->createMock(TypographyService::class);
        $typography->method('get')->willReturn(['heading' => [], 'text' => []]);
        Container::set(TypographyService::class, $typography);
    }

}
