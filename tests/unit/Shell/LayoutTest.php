<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell;

use TheSaiged\Core\Container;
use TheSaiged\Pages\Page;
use TheSaiged\Pages\PageStatus;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Sections\PageCover\PageCoverSection;
use TheSaiged\Shell\Footer\FooterShell;
use TheSaiged\Shell\Header\HeaderLink;
use TheSaiged\Shell\Header\HeaderShell;
use TheSaiged\Shell\Layout;
use TheSaiged\Shell\ShellService;
use TheSaiged\Tests\TestCase;

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
        $this->assertStringContainsString('/css/public/search.css',   $html);
        $this->assertStringContainsString('/css/public/cookie-banner.css', $html);
        $this->assertStringContainsString('/js/main.js',              $html);
    }

    function testRenderOmitsAnalyticsWhenMeasurementIdUnset (): void {
        putenv('GA_MEASUREMENT_ID');
        $this->mockShells(HeaderShell::default(), FooterShell::default());

        $html = Container::get(Layout::class)->render(new Page(1, 'p', 't', null, PageStatus::Published, []));

        $this->assertStringNotContainsString('googletagmanager.com', $html);
    }

    function testRenderIncludesAnalyticsWithConsentModeWhenMeasurementIdSet (): void {
        putenv('GA_MEASUREMENT_ID=G-TEST123');
        try {
            $this->mockShells(HeaderShell::default(), FooterShell::default());

            $html = Container::get(Layout::class)->render(new Page(1, 'p', 't', null, PageStatus::Published, []));

            $this->assertStringContainsString('googletagmanager.com/gtag/js?id=G-TEST123', $html);
            $this->assertStringContainsString("gtag('config', 'G-TEST123')",                $html);
            $this->assertStringContainsString("gtag('consent', 'default'",                  $html);
            $this->assertStringContainsString('cookie_consent',                             $html);
        } finally {
            putenv('GA_MEASUREMENT_ID');
        }
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

    function testRenderOmitsHeaderOverlayClassWhenFirstSectionIsNotFullPageImage (): void {
        $this->mockShells(HeaderShell::default(), FooterShell::default());

        $page = new Page(1, 'p', 't', null, PageStatus::Published, [new ArticleSection('x')]);
        $html = Container::get(Layout::class)->render($page);

        $this->assertStringContainsString('<body>', $html);
        $this->assertStringNotContainsString('header-overlay', $html);
    }

    function testRenderAddsHeaderOverlayClassWhenFirstSectionIsFullPageImage (): void {
        $this->mockShells(HeaderShell::default(), FooterShell::default());

        $page = new Page(1, 'p', 't', null, PageStatus::Published, [
            new PageCoverSection(uploadId: 1, eyebrow: 'e', heading: 'h'),
        ]);
        $html = Container::get(Layout::class)->render($page);

        $this->assertStringContainsString('<body class="header-overlay">', $html);
    }

    private function mockShells (HeaderShell $header, FooterShell $footer): void {
        $service = $this->createMock(ShellService::class);
        $service->method('get')->willReturnMap([
            ['header', $header],
            ['footer', $footer],
        ]);
        Container::set(ShellService::class, $service);
    }

}
