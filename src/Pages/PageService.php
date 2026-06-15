<?php declare(strict_types = 1);

namespace TheSaiged\Pages;

/**
 * Business operations for Pages. Takes domain value objects (PageId,
 * PageCreate, PageUpdate) — never raw HTTP types or arrays. Returns
 * nullable / bool for "missing" cases; throws domain exceptions only
 * for cases that can't be cleanly expressed as a return (path uniqueness).
 *
 * `findPublishedByPath` enforces the public visibility rule one layer up
 * from the repo, so "what's public" lives in service code rather than
 * being baked into SQL.
 */
final readonly class PageService {

    function __construct (
        private PageRepository $repo,
    ) {}

    /** @return list<PageSummary> */
    function list (): array {
        return $this->repo->listPages();
    }

    function get (PageId $id): ?Page {
        return $this->repo->getById($id->value);
    }

    /**
     * Path lookup that bypasses the publication filter — used by preview
     * / admin flows that need access to drafts.
     */
    function getByPath (string $path): ?Page {
        return $this->repo->getByPath($path);
    }

    /**
     * Public-facing path lookup: returns the page only if currently
     * published. Filter lives here, not in the repo.
     */
    function findPublishedByPath (string $path): ?Page {
        $page = $this->repo->getByPath($path);
        return $page !== null && $page->status === PageStatus::Published ? $page : null;
    }

    /**
     * @throws DuplicatePathException when the requested path is already taken
     */
    function create (PageCreate $req): int {
        return $this->repo->create($req->path, $req->title);
    }

    /**
     * @return bool false if id doesn't exist
     */
    function update (PageId $id, PageUpdate $req): bool {
        $existing = $this->repo->getById($id->value);
        if ($existing === null)
            return false;

        $this->repo->save(new Page(
            id:       $existing->id,
            path:     $existing->path,
            title:    $req->title,
            metaDesc: $req->metaDesc,
            status:   $req->status,
            sections: $req->sections,
        ));
        return true;
    }

    /**
     * Deep-copy a page into a new row. Returns the new id, or null if the
     * source id doesn't exist.
     *
     * @throws DuplicatePathException when the target path is already taken
     */
    function copy (PageId $sourceId, PageCreate $target): ?int {
        $source = $this->repo->getById($sourceId->value);
        if ($source === null)
            return null;

        return $this->repo->insert(
            path:     $target->path,
            title:    $target->title,
            metaDesc: $source->metaDesc,
            status:   PageStatus::Draft,
            sections: $source->sections,
        );
    }

    function delete (PageId $id): bool {
        return $this->repo->delete($id->value);
    }

}
