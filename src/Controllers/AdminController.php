<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Exception\BadRequestException;
use TheSaiged\Core\Http\Exception\ConflictException;
use TheSaiged\Core\Http\Exception\HttpException;
use TheSaiged\Core\Http\Exception\NotFoundException;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Pages\DuplicatePathException;
use TheSaiged\Pages\PageCreate;
use TheSaiged\Pages\PageId;
use TheSaiged\Pages\PageService;
use TheSaiged\Pages\PageSummary;
use TheSaiged\Pages\PageUpdate;
use TheSaiged\Sections\SectionFactory;

/**
 * Admin JSON API for Pages. Each endpoint extracts inputs into value
 * objects (PageId, PageCreate, PageUpdate), delegates to PageService,
 * and serializes the response via entity toArray() methods.
 *
 * Validation lives in the value objects' fromArray factories — they
 * throw InvalidDataException on bad shape. Domain errors from the
 * service (DuplicatePathException) bubble up. Both are mapped to HTTP
 * centrally in onError so individual endpoint methods stay free of
 * try/catch boilerplate.
 */
final class AdminController {

    use Controller;

    function __construct (
        private PageService $pages,
    ) {}

    /**
     * @throws BadRequestException
     */
    function listPages (Request $request): Response {
        $summaries = $this->pages->list();
        $serialized = array_map(
            fn (PageSummary $summary): array => $summary->toArray(),
            $summaries,
        );
        return Response::json(['pages' => $serialized]);
    }

    /**
     * @throws BadRequestException  when the id path-param is non-numeric or non-positive
     * @throws NotFoundException    when no page exists for the given id
     */
    function getPage (Request $request): Response {
        $rawId = $request->path->getInt('id')
            ?? throw new BadRequestException('Invalid page id');
        $page = $this->pages->get(new PageId($rawId));
        if ($page === null)
            throw new NotFoundException();
        return Response::json(['page' => $page->toArray()]);
    }

    /**
     * @throws BadRequestException  on missing / malformed body or invalid PageCreate fields
     * @throws ConflictException    on duplicate path (via onError mapping of DuplicatePathException)
     */
    function createPage (Request $request): Response {
        $body = $request->bodyObject()
            ?? throw new BadRequestException('Expected JSON body');
        $newId = $this->pages->create(PageCreate::fromArray($body));
        return Response::json(['ok' => true, 'id' => $newId], 201);
    }

    /**
     * @throws BadRequestException  invalid id / missing body / invalid PageUpdate fields
     * @throws NotFoundException    when no page exists for the given id
     */
    function updatePage (Request $request): Response {
        $rawId = $request->path->getInt('id')
            ?? throw new BadRequestException('Invalid page id');
        $pageId = new PageId($rawId);
        $body = $request->bodyObject()
            ?? throw new BadRequestException('Expected JSON body');

        $updated = $this->pages->update($pageId, PageUpdate::fromArray($body));
        if (!$updated)
            throw new NotFoundException();
        return Response::json(['ok' => true, 'id' => $pageId->value]);
    }

    /**
     * @throws BadRequestException  invalid id / missing body / invalid PageCreate fields
     * @throws NotFoundException    when the source id doesn't exist
     * @throws ConflictException    on duplicate target path (via onError)
     */
    function copyPage (Request $request): Response {
        $rawId = $request->path->getInt('id')
            ?? throw new BadRequestException('Invalid page id');
        $sourceId = new PageId($rawId);
        $body = $request->bodyObject()
            ?? throw new BadRequestException('Expected JSON body');

        $newId = $this->pages->copy($sourceId, PageCreate::fromArray($body));
        if ($newId === null)
            throw new NotFoundException();
        return Response::json(['ok' => true, 'id' => $newId], 201);
    }

    /**
     * @throws BadRequestException  invalid id path-param
     * @throws NotFoundException    when no page exists for the given id
     */
    function deletePage (Request $request): Response {
        $rawId = $request->path->getInt('id')
            ?? throw new BadRequestException('Invalid page id');
        $pageId = new PageId($rawId);

        if (!$this->pages->delete($pageId))
            throw new NotFoundException();
        return Response::json(['ok' => true, 'id' => $pageId->value]);
    }

    function listSections (Request $request): Response {
        return Response::json(['sections' => SectionFactory::list()]);
    }

    function notFound (Request $request): Response {
        return Response::json(['error' => 'Not found'], 404);
    }

    /**
     * Central domain → HTTP exception mapping. Endpoint methods stay free
     * of try/catch boilerplate; thrown domain exceptions land here and
     * get translated to the right status + message.
     */
    function onError (Throwable $exception, Request $request): Response {
        if ($exception instanceof InvalidDataException)
            $exception = new BadRequestException($exception->getMessage(), $exception);
        if ($exception instanceof DuplicatePathException)
            $exception = new ConflictException($exception->getMessage(), $exception);
        if ($exception instanceof HttpException)
            return Response::json(['error' => $exception->getMessage()], $exception->status);
        error_log((string) $exception);
        return Response::json(['error' => 'Internal error'], 500);
    }

}
