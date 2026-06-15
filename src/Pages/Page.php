<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use ReflectionClass;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;
use TheSaiged\Sections\SectionFactory;

/**
 * Full Page detail — every field populated from the DB. List views use
 * the leaner PageSummary projection instead, so any field absent here
 * is genuinely missing data (and would throw via fromDbRow), not just
 * "not loaded".
 *
 * metaDesc stays nullable because it's a legitimately optional content
 * field — the user can leave it blank.
 */
final readonly class Page {

    /** @param list<Section> $sections */
    function __construct (
        public int        $id,
        public string     $path,
        public string     $title,
        public ?string    $metaDesc,
        public PageStatus $status,
        public array      $sections,
    ) {}

    /** @param array<string, mixed> $row */
    static function fromDbRow (array $row): self {
        $id       = $row['id']        ?? null;
        $path     = $row['path']      ?? null;
        $title    = $row['title']     ?? null;
        $metaDesc = $row['meta_desc'] ?? null;
        $status   = $row['status']    ?? null;
        $content  = $row['content']   ?? null;

        if (!is_int($id) || !is_string($path) || !is_string($title) || !is_string($status))
            throw new InvalidDataException('page row');
        if ($metaDesc !== null && !is_string($metaDesc))
            throw new InvalidDataException('page row', 'meta_desc must be string or null');

        $statusEnum = PageStatus::tryFrom($status)
            ?? throw new InvalidDataException('page row', "unknown status: $status");

        return new self(
            id:       $id,
            path:     $path,
            title:    $title,
            metaDesc: $metaDesc,
            status:   $statusEnum,
            sections: self::decodeSections($content),
        );
    }

    /**
     * Canonical API shape — what the admin JSON endpoints return. Sections
     * are serialized via SectionFactory's wrap so the entity owns its own
     * external representation; controllers don't need to know the field
     * layout.
     *
     * @return array<string, mixed>
     */
    function toArray (): array {
        return [
            'id'       => $this->id,
            'path'     => $this->path,
            'title'    => $this->title,
            'metaDesc' => $this->metaDesc,
            'status'   => $this->status->value,
            'sections' => array_map(SectionFactory::toArray(...), $this->sections),
        ];
    }

    function render (): string {
        $title    = htmlspecialchars($this->title, ENT_QUOTES);
        $metaDesc = $this->metaDesc !== null
            ? '<meta name="description" content="' . htmlspecialchars($this->metaDesc, ENT_QUOTES) . '">'
            : '';
        $assets   = $this->renderAssetTags();
        $body     = '';
        foreach ($this->sections as $section)
            $body .= $section->render();

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>$title</title>
                $metaDesc
                <link rel="stylesheet" href="/css/public/main.css">
                $assets
            </head>
            <body>
                $body
            </body>
            </html>
            HTML;
    }

    /** @return list<Section> */
    private static function decodeSections (mixed $content): array {
        if ($content === null)
            return [];
        if (!is_string($content))
            throw new InvalidDataException('page content');

        $decoded = json_decode($content, true);
        if (!is_array($decoded))
            throw new InvalidDataException('page content JSON');

        $sections = [];
        foreach ($decoded as $sectionData) {
            if (!is_array($sectionData))
                throw new InvalidDataException('section data');
            $sections[] = SectionFactory::fromArray($sectionData);
        }
        return $sections;
    }

    /**
     * Collects unique section classes used by this page and emits <link> and
     * <script type="module"> tags for each declared asset. Folder name is
     * derived from the class' parent namespace via reflection, so the URL is
     * decoupled from the lowercase type identifier.
     */
    private function renderAssetTags (): string {
        $seen = [];
        $tags = [];
        foreach ($this->sections as $section) {
            $class = $section::class;
            if (isset($seen[$class]))
                continue;
            $seen[$class] = true;

            $folder = basename(str_replace('\\', '/', (new ReflectionClass($class))->getNamespaceName()));
            foreach ($class::cssAssets() as $file)
                $tags[] = "<link rel=\"stylesheet\" href=\"/sections/$folder/$file\">";
            foreach ($class::jsAssets() as $file)
                $tags[] = "<script type=\"module\" src=\"/sections/$folder/$file\"></script>";
        }
        return implode("\n    ", $tags);
    }

}
