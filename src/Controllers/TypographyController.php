<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Exception\BadRequestException;
use TheSaiged\Core\Http\Exception\HttpException;
use TheSaiged\Core\Http\Exception\NotFoundException;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Typography\FontRole;
use TheSaiged\Typography\FontStyle;
use TheSaiged\Typography\TypographyService;
use TheSaiged\Uploads\UploadId;

/**
 * Admin JSON API for Typography (custom font faces per role). Unlike
 * Uploads/Pages there's no per-resource GET/PUT — faces are a list you
 * add to and remove from, keyed by role.
 */
final class TypographyController {

    use Controller;

    function __construct (
        private TypographyService $typography,
    ) {}

    function get (Request $request): Response {
        return Response::json($this->typography->get());
    }

    /**
     * @throws BadRequestException unknown role / missing body / invalid shape
     */
    function addFace (Request $request): Response {
        $role = FontRole::tryFrom($request->path->getString('role') ?? '')
            ?? throw new BadRequestException('Unknown font role');

        $body      = $request->bodyObject() ?? throw new BadRequestException('Expected JSON body');
        $uploadId  = $body['uploadId']  ?? null;
        $weightMin = $body['weightMin'] ?? null;
        $weightMax = $body['weightMax'] ?? null;
        $styleRaw  = $body['style']     ?? null;

        if (!is_int($uploadId) || !is_int($weightMin) || !is_int($weightMax) || !is_string($styleRaw))
            throw new BadRequestException('Expected uploadId, weightMin, weightMax (ints) and style (string)');
        $style = FontStyle::tryFrom($styleRaw)
            ?? throw new BadRequestException('Unknown font style');

        $face = $this->typography->addFace($role, new UploadId($uploadId), $weightMin, $weightMax, $style);
        return Response::json(['ok' => true, 'face' => $face], 201);
    }

    /**
     * @throws BadRequestException invalid id path-param
     * @throws NotFoundException   when no face exists for the given id
     */
    function removeFace (Request $request): Response {
        $id = $request->path->getInt('id') ?? throw new BadRequestException('Invalid face id');

        if (!$this->typography->removeFace($id))
            throw new NotFoundException();
        return Response::json(['ok' => true, 'id' => $id]);
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
