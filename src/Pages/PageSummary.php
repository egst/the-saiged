<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

use TheSaiged\Core\InvalidDataException;

/**
 * Lightweight projection of a page row — what listPages returns. Holds
 * only the fields needed for the sidebar / list view (no metaDesc, no
 * sections), so a missing field here is genuinely "not part of this
 * projection" rather than "happens to be null in the source".
 *
 * Self-serializes to its API shape via toArray() — controllers don't
 * need to know which fields are public.
 */
final readonly class PageSummary {

    function __construct (
        public int        $id,
        public string     $path,
        public string     $title,
        public PageStatus $status,
    ) {}

    /** @param array<string, mixed> $row */
    static function fromDbRow (array $row): self {
        $id     = $row['id']     ?? null;
        $path   = $row['path']   ?? null;
        $title  = $row['title']  ?? null;
        $status = $row['status'] ?? null;

        if (!is_int($id) || !is_string($path) || !is_string($title) || !is_string($status))
            throw new InvalidDataException('page summary row');

        $statusEnum = PageStatus::tryFrom($status)
            ?? throw new InvalidDataException('page summary row', "unknown status: $status");

        return new self($id, $path, $title, $statusEnum);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'id'     => $this->id,
            'path'   => $this->path,
            'title'  => $this->title,
            'status' => $this->status->value,
        ];
    }

}
