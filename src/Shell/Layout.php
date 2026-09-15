<?php declare(strict_types = 1);

namespace TheSaiged\Shell;

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
                $shellAssets
                {$page->assetTags()}
            </head>
            <body>
                {$header->render()}
                {$page->bodyHtml()}
                {$footer->render()}
                <script type="module" src="/js/main.js"></script>
            </body>
            </html>
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
