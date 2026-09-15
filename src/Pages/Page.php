<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use ReflectionClass;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\FullPageImageSection;
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
 *
 * searchable defaults to true and sits last with a default value
 * (rather than in field order next to status, where it conceptually
 * belongs) purely to avoid updating every positional `new Page(...)`
 * call across the codebase — it's genuinely optional, unlike the fields
 * before it.
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
        public bool       $searchable = true,
    ) {}

    /** @param array<string, mixed> $row */
    static function fromDbRow (array $row): self {
        $id         = $row['id']         ?? null;
        $path       = $row['path']       ?? null;
        $title      = $row['title']      ?? null;
        $metaDesc   = $row['meta_desc']  ?? null;
        $status     = $row['status']     ?? null;
        $content    = $row['content']    ?? null;
        $searchable = $row['searchable'] ?? true;

        if (!is_int($id) || !is_string($path) || !is_string($title) || !is_string($status))
            throw new InvalidDataException('page row');
        if ($metaDesc !== null && !is_string($metaDesc))
            throw new InvalidDataException('page row', 'meta_desc must be string or null');
        if (!is_bool($searchable) && !is_int($searchable))
            throw new InvalidDataException('page row', 'searchable must be boolean');

        $statusEnum = PageStatus::tryFrom($status)
            ?? throw new InvalidDataException('page row', "unknown status: $status");

        return new self(
            id:         $id,
            path:       $path,
            title:      $title,
            metaDesc:   $metaDesc,
            status:     $statusEnum,
            sections:   self::decodeSections($content),
            searchable: (bool) $searchable,
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
            'id'         => $this->id,
            'path'       => $this->path,
            'title'      => $this->title,
            'metaDesc'   => $this->metaDesc,
            'status'     => $this->status->value,
            'sections'   => array_map(SectionFactory::toArray(...), $this->sections),
            'searchable' => $this->searchable,
        ];
    }

    /** @return array{title: string, html: string, cssLinks: list<string>} */
    function partial (): array {
        return [
            'title'    => $this->title,
            'html'     => $this->bodyHtml(),
            'cssLinks' => $this->cssLinkUrls(),
        ];
    }

    /**
     * Rendered `<meta name="description">` tag, or '' when unset. Public so
     * Layout can assemble the full document's <head> without duplicating
     * the escaping/formatting rule.
     */
    function metaDescTag (): string {
        return $this->metaDesc !== null
            ? '<meta name="description" content="' . htmlspecialchars($this->metaDesc, ENT_QUOTES) . '">'
            : '';
    }

    /** <link>/<script> tags for every section's declared assets, deduplicated. Public for Layout. */
    function assetTags (): string {
        $tags = [];
        foreach ($this->sectionFolders() as $class => $folder) {
            foreach ($class::cssAssets() as $file)
                $tags[] = "<link rel=\"stylesheet\" href=\"/sections/$folder/$file\">";
            foreach ($class::jsAssets() as $file)
                $tags[] = "<script type=\"module\" src=\"/sections/$folder/$file\"></script>";
        }
        return implode("\n    ", $tags);
    }

    /**
     * Whether the header should overlay this page (transparent, fixed) vs
     * sit inline above it (solid, sticky, taking up its own space) — true
     * only when the page opens on a section dark enough for the header's
     * white-text overlay state to read against, see FullPageImageSection.
     * Public for Layout.
     */
    function hasFullPageHero (): bool {
        return ($this->sections[0] ?? null) instanceof FullPageImageSection;
    }

    /** Concatenated section markup, in section order. Public for Layout. */
    function bodyHtml (): string {
        $body = '';
        foreach ($this->sections as $section)
            $body .= $section->render();
        return $body;
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

    /** @return array<string, string> map of section class => folder name, deduplicated */
    private function sectionFolders (): array {
        $folders = [];
        foreach ($this->sections as $section) {
            $class = $section::class;
            if (isset($folders[$class]))
                continue;
            $folders[$class] = basename(str_replace('\\', '/', (new ReflectionClass($class))->getNamespaceName()));
        }
        return $folders;
    }

    /** @return list<string> */
    private function cssLinkUrls (): array {
        $urls = [];
        foreach ($this->sectionFolders() as $class => $folder)
            foreach ($class::cssAssets() as $file)
                $urls[] = "/sections/$folder/$file";
        return $urls;
    }

}
