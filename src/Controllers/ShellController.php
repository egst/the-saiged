<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Exception\BadRequestException;
use TheSaiged\Core\Http\Exception\HttpException;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\ShellService;

/**
 * Admin JSON API for Shell (Header, Footer). Unlike Pages, there's no
 * create endpoint — a type either has a saved row or falls back to its
 * default(); GET always succeeds for a known type, PUT always upserts.
 * Unknown {type} surfaces as InvalidDataException from ShellService,
 * mapped to 400 the same way SectionFactory's unknown-type case is.
 */
final class ShellController {

    use Controller;

    function __construct (
        private ShellService $shells,
    ) {}

    /**
     * @throws BadRequestException  unknown shell type
     */
    function get (Request $request): Response {
        $type = $request->path->getString('type')
            ?? throw new BadRequestException('Missing shell type');
        return Response::json(['type' => $type, 'data' => $this->shells->get($type)->toArray()]);
    }

    /**
     * @throws BadRequestException  unknown shell type / missing body / invalid data shape
     */
    function put (Request $request): Response {
        $type = $request->path->getString('type')
            ?? throw new BadRequestException('Missing shell type');
        $body = $request->bodyObject()
            ?? throw new BadRequestException('Expected JSON body');

        $this->shells->save($type, $body);
        return Response::json(['ok' => true, 'type' => $type]);
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
