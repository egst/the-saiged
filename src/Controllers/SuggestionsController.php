<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Exception\BadRequestException;
use TheSaiged\Core\Http\Exception\HttpException;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Search\SuggestionsService;

/**
 * The "naseptávač" list — get() is public (the search modal's typing
 * animation fetches it unauthenticated), put() is admin-guarded in
 * Entry::routes() same as Shell/Typography's own settings endpoints.
 */
final class SuggestionsController {

    use Controller;

    function __construct (
        private SuggestionsService $suggestions,
    ) {}

    function get (Request $request): Response {
        return Response::json(['suggestions' => $this->suggestions->list()]);
    }

    /**
     * @throws BadRequestException missing body / invalid shape
     */
    function put (Request $request): Response {
        $body = $request->bodyObject() ?? throw new BadRequestException('Expected JSON body');
        $raw  = $body['suggestions'] ?? null;
        if (!is_array($raw) || !array_is_list($raw))
            throw new BadRequestException('Expected suggestions to be a list');

        $suggestions = [];
        foreach ($raw as $item) {
            if (!is_string($item))
                throw new BadRequestException('Each suggestion must be a string');
            $suggestions[] = $item;
        }

        $this->suggestions->save($suggestions);
        return Response::json(['ok' => true]);
    }

    function onError (Throwable $exception, Request $request): Response {
        if ($exception instanceof InvalidDataException)
            $exception = new BadRequestException($exception->getMessage(), $exception);
        if ($exception instanceof HttpException)
            return Response::json(['error' => $exception->getMessage()], $exception->status);
        error_log((string) $exception);
        return Response::json(['error' => 'Internal error'], 500);
    }

}
