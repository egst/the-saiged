<?php declare(strict_types = 1);

namespace TheSaiged\Shell;

use TheSaiged\Pages\Page;
use TheSaiged\Shell\Footer\FooterShell;
use TheSaiged\Shell\Header\HeaderShell;
use TheSaiged\Typography\FontRole;
use TheSaiged\Typography\TypographyService;

/**
 * Composes the full public document: header, page body, footer. Ordering
 * is hardcoded here rather than data-driven — there's no "slot" concept,
 * the same way there's no ordering config for where a section's assets go
 * relative to another's. Adding a third chrome element means adding a
 * line here, not a schema change.
 *
 * Page::partial() (used by the overlay navigation) is untouched by this —
 * the header/footer stay on screen across SPA navigation, only the body
 * swaps. Typography is likewise untouched there — it's a <head>-only
 * concern (custom @font-face + --serif/--sans overrides), and the head
 * never changes across SPA navigation.
 */
final readonly class Layout {

    private const FAMILY_NAME = [
        'heading' => 'CustomHeadingFont',
        'text'    => 'CustomTextFont',
    ];

    private const CSS_VAR_FALLBACK = [
        'heading' => ['--serif', "Georgia, serif"],
        'text'    => ['--sans',  "Arial, Helvetica, sans-serif"],
    ];

    function __construct (
        private ShellService      $shells,
        private TypographyService $typography,
    ) {}

    function render (Page $page): string {
        $header = $this->shells->get(HeaderShell::type());
        $footer = $this->shells->get(FooterShell::type());

        $title = htmlspecialchars($page->title, ENT_QUOTES);

        $shellAssets      = $this->shellAssetTags($header) . "\n    " . $this->shellAssetTags($footer);
        $typographyStyle  = $this->typographyStyleTag($this->typography->get());

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>$title</title>
                {$page->metaDescTag()}
                <link rel="stylesheet" href="/css/public/main.css">
                $typographyStyle
                <link rel="stylesheet" href="/css/public/overlay.css">
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

    /**
     * Builds @font-face rules + --serif/--sans overrides for whichever
     * roles have at least one uploaded face. A role with none emits
     * nothing here, leaving main.css's own Kalice/Arial declaration as
     * the default — no fallback logic needed beyond "don't touch it".
     *
     * @param array<string, list<array<string, mixed>>> $facesByRole
     */
    private function typographyStyleTag (array $facesByRole): string {
        $faceRules = '';
        $overrides = '';

        foreach (FontRole::cases() as $role) {
            $faces = $facesByRole[$role->value] ?? [];
            if (empty($faces))
                continue;

            $family = self::FAMILY_NAME[$role->value];
            foreach ($faces as $face) {
                $upload = $face['upload'] ?? null;
                if (!is_array($upload))
                    continue;
                $url    = htmlspecialchars((string) $upload['originalUrl'], ENT_QUOTES);
                $format = self::fontFormat((string) $upload['filename']);
                $faceRules .= <<<CSS

                    @font-face {
                        font-family: '$family';
                        src: url('$url') format('$format');
                        font-weight: {$face['weightMin']} {$face['weightMax']};
                        font-style: {$face['style']};
                        font-display: swap;
                    }
                    CSS;
            }

            [$cssVar, $fallback] = self::CSS_VAR_FALLBACK[$role->value];
            $overrides .= "    $cssVar: '$family', $fallback;\n";
        }

        if ($faceRules === '')
            return '';

        return "<style>$faceRules\n:root {\n$overrides}\n</style>";
    }

    private static function fontFormat (string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'ttf'   => 'truetype',
            'woff'  => 'woff',
            'woff2' => 'woff2',
            default => 'opentype',
        };
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
