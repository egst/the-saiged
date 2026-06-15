<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;
use TheSaiged\Sections\SectionFactory;

/**
 * Payload for PageService::update. Validates shape and parses sections
 * via SectionFactory — both shape and section-type errors surface as
 * InvalidDataException, which the controller's onError maps to a 400.
 *
 * Path is intentionally NOT here: the update endpoint never moves a page
 * to a new path (use copy + delete for that). The existing row's path
 * is kept by PageService::update.
 */
final readonly class PageUpdate {

    /** @param list<Section> $sections */
    function __construct (
        public string     $title,
        public ?string    $metaDesc,
        public PageStatus $status,
        public array      $sections,
    ) {}

    /**
     * @param  array<string, mixed> $body
     * @throws InvalidDataException on missing / empty / wrong-typed fields
     *                              or invalid section data (unknown type,
     *                              missing fields — propagated from
     *                              SectionFactory::fromArray)
     */
    static function fromArray (array $body): self {
        $title = $body['title'] ?? null;
        if (!is_string($title) || $title === '')
            throw new InvalidDataException('PageUpdate', 'title must be a non-empty string');

        $metaDesc = $body['metaDesc'] ?? null;
        if ($metaDesc !== null && !is_string($metaDesc))
            throw new InvalidDataException('PageUpdate', 'metaDesc must be a string or null');

        $statusRaw = $body['status'] ?? null;
        if (!is_string($statusRaw))
            throw new InvalidDataException('PageUpdate', 'status must be a string');
        $status = PageStatus::tryFrom($statusRaw)
            ?? throw new InvalidDataException('PageUpdate', "unknown status: $statusRaw");

        $rawSections = $body['sections'] ?? null;
        if (!is_array($rawSections) || !array_is_list($rawSections))
            throw new InvalidDataException('PageUpdate', 'sections must be a list');

        $sections = [];
        foreach ($rawSections as $sectionData) {
            if (!is_array($sectionData))
                throw new InvalidDataException('PageUpdate', 'each section must be an object');
            $sections[] = SectionFactory::fromArray($sectionData);
        }

        return new self($title, $metaDesc, $status, $sections);
    }

}
