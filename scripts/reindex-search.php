<?php declare(strict_types = 1);

/**
 * One-off / re-runnable backfill for pages.search_text. M012PagesSearch
 * only adds the column (schema change) — it has no way to run
 * SearchTextExtractor over existing rows, so anything saved before that
 * migration stays NULL (and therefore unsearchable) until either its
 * next real edit or this script. Safe to re-run any time, e.g. after
 * changing SearchTextExtractor's logic — every page is just re-saved
 * unchanged, which recomputes search_text as a side effect.
 */

require_once __DIR__ . '/../src/bootstrap.php';

use TheSaiged\Core\Container;
use TheSaiged\Pages\PageId;
use TheSaiged\Pages\PageRepository;
use TheSaiged\Pages\PageService;

$pages = Container::get(PageService::class);
$repo  = Container::get(PageRepository::class);

foreach ($pages->list() as $summary) {
    $page = $pages->get(new PageId($summary->id));
    if ($page === null)
        continue;
    $repo->save($page);
    echo "Reindexed #{$page->id} {$page->path}\n";
}
