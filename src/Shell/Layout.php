<?php declare(strict_types = 1);

namespace TheSaiged\Shell;

use TheSaiged\Core\Env;
use TheSaiged\Pages\Page;
use TheSaiged\Shell\Footer\FooterShell;
use TheSaiged\Shell\Header\HeaderShell;

/**
 * Composes the full public document: header, page body, footer. Ordering
 * is hardcoded here rather than data-driven — there's no "slot" concept,
 * the same way there's no ordering config for where a section's assets go
 * relative to another's. Adding a third chrome element means adding a
 * line here, not a schema change.
 *
 * Page::partial() (used by the overlay navigation) is untouched by this —
 * the header/footer stay on screen across SPA navigation, only the body
 * swaps.
 */
final readonly class Layout {

    function __construct (
        private ShellService $shells,
    ) {}

    function render (Page $page): string {
        $header = $this->shells->get(HeaderShell::type());
        $footer = $this->shells->get(FooterShell::type());

        $title = htmlspecialchars($page->title, ENT_QUOTES);

        $shellAssets = $this->shellAssetTags($header) . "\n    " . $this->shellAssetTags($footer);
        $analytics   = $this->analyticsTag();
        $bodyClass   = $page->hasFullPageHero() ? ' class="header-overlay"' : '';

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>$title</title>
                {$page->metaDescTag()}
                <link rel="stylesheet" href="/css/public/main.css">
                <link rel="stylesheet" href="/css/public/overlay.css">
                <link rel="stylesheet" href="/css/public/search.css">
                <link rel="stylesheet" href="/css/public/cookie-banner.css">
                $shellAssets
                {$page->assetTags()}
                $analytics
            </head>
            <body$bodyClass>
                {$header->render()}
                {$page->bodyHtml()}
                {$footer->render()}
                <script type="module" src="/js/main.js"></script>
            </body>
            </html>
            HTML;
    }

    /**
     * Google Analytics via Consent Mode: gtag.js and the config call always
     * load, but with the default consent state read straight from the
     * "cookie_consent" cookie (or denied, if it's not set yet) — this lets
     * GA keep sending cookieless, aggregated pings even before/without
     * consent, and only start setting its own cookies once the visitor
     * accepts. cookie-consent.js (public/js) is the only other place that
     * reads/writes this cookie — its name and values must stay in sync
     * with what's checked here. Emits nothing when unconfigured, so local
     * dev traffic never reaches real GA.
     */
    private function analyticsTag (): string {
        $id = Env::optional('GA_MEASUREMENT_ID', '');
        if ($id === '')
            return '';

        $id = htmlspecialchars($id, ENT_QUOTES);
        return <<<HTML
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag () { dataLayer.push(arguments); }
                (function () {
                    var match   = document.cookie.match(/(?:^|; )cookie_consent=([^;]*)/);
                    var consent = match ? decodeURIComponent(match[1]) : null;
                    gtag('consent', 'default', {analytics_storage: consent === 'accepted' ? 'granted' : 'denied'});
                })();
                gtag('js', new Date());
                gtag('config', '$id');
            </script>
            <script async src="https://www.googletagmanager.com/gtag/js?id=$id"></script>
            HTML;
    }

    private function shellAssetTags (Shell $shell): string {
        $folder = self::folderFor($shell::class);
        $tags   = [];
        foreach ($shell::cssAssets() as $file)
            $tags[] = "<link rel=\"stylesheet\" href=\"/shell/$folder/$file\">";
        foreach ($shell::jsAssets() as $file)
            $tags[] = "<script type=\"module\" src=\"/shell/$folder/$file\"></script>";
        return implode("\n    ", $tags);
    }

    /** @param class-string<Shell> $class */
    private static function folderFor (string $class): string {
        $parts = explode('\\', $class);
        return $parts[count($parts) - 2];
    }

}
