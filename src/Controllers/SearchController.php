<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Search\SearchResult;
use TheSaiged\Search\SearchService;

/**
 * Public search endpoint — no auth, visitors call this directly. A
 * missing/empty ?q just yields an empty result list, not an error;
 * there's no other structured input to validate here.
 */
final class SearchController {

    use Controller;

    function __construct (
        private SearchService $search,
    ) {}

    function search (Request $request): Response {
        $query   = $request->query->getString('q') ?? '';
        $results = $this->search->search($query);
        return Response::json([
            'results' => array_map(fn (SearchResult $r): array => $r->toArray(), $results),
        ]);
    }

    function onError (Throwable $exception, Request $request): Response {
        error_log((string) $exception);
        return Response::json(['error' => 'Internal error'], 500);
    }

}
