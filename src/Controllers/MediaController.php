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
use TheSaiged\Uploads\Upload;
use TheSaiged\Uploads\UnsupportedMediaTypeException;
use TheSaiged\Uploads\UploadId;
use TheSaiged\Uploads\UploadInput;
use TheSaiged\Uploads\UploadService;

/**
 * Admin JSON API for media uploads. Endpoint methods extract value
 * objects (UploadId, UploadInput), delegate to UploadService, serialize
 * via Upload::toArray. The /api/* notFound route belongs to
 * AdminController — no point duplicating it here.
 */
final class MediaController {

    use Controller;

    function __construct (
        private UploadService $uploads,
    ) {}

    /**
     * @throws BadRequestException
     */
    function listUploads (Request $request): Response {
        $entries    = $this->uploads->list();
        $serialized = array_map(
            fn (Upload $upload): array => $upload->toArray(),
            $entries,
        );
        return Response::json(['uploads' => $serialized]);
    }

    /**
     * @throws BadRequestException  malformed multipart / disallowed MIME type
     *                              (UnsupportedMediaTypeException → mapped via onError)
     */
    function createUpload (Request $request): Response {
        $upload = $this->uploads->create(UploadInput::fromGlobals());
        return Response::json(['ok' => true, 'upload' => $upload->toArray()], 201);
    }

    /**
     * Ensure (generate-if-missing) a sized variant of an image upload, and
     * return its public URL. Idempotent — variants are cached on disk.
     *
     * @throws BadRequestException  invalid id / missing-or-bad width/height
     * @throws NotFoundException    when the upload doesn't exist or isn't
     *                              an image (videos can't have variants)
     */
    function ensureVariant (Request $request): Response {
        $rawId = $request->path->getInt('id')
            ?? throw new BadRequestException('Invalid upload id');
        $uploadId = new UploadId($rawId);
        $body = $request->bodyObject()
            ?? throw new BadRequestException('Expected JSON body');

        $width  = $body['width']  ?? null;
        $height = $body['height'] ?? null;
        if (!is_int($width)  || $width  <= 0 || $width  > 8000)
            throw new BadRequestException('width must be a positive integer (max 8000)');
        if (!is_int($height) || $height <= 0 || $height > 8000)
            throw new BadRequestException('height must be a positive integer (max 8000)');

        $url = $this->uploads->ensureVariant($uploadId, $width, $height);
        if ($url === null)
            throw new NotFoundException();
        return Response::json(['ok' => true, 'url' => $url], 201);
    }

    /**
     * @throws BadRequestException  invalid id path-param
     * @throws NotFoundException    when no upload exists for the given id
     */
    function deleteUpload (Request $request): Response {
        $rawId = $request->path->getInt('id')
            ?? throw new BadRequestException('Invalid upload id');
        $uploadId = new UploadId($rawId);

        if (!$this->uploads->delete($uploadId))
            throw new NotFoundException();
        return Response::json(['ok' => true, 'id' => $uploadId->value]);
    }

    function onError (Throwable $exception, Request $request): Response {
        if ($exception instanceof InvalidDataException)
            $exception = new BadRequestException($exception->getMessage(), $exception);
        if ($exception instanceof UnsupportedMediaTypeException)
            $exception = new BadRequestException($exception->getMessage(), $exception);
        if ($exception instanceof HttpException)
            return Response::json(['error' => $exception->getMessage()], $exception->status);
        error_log((string) $exception);
        return Response::json(['error' => 'Internal error'], 500);
    }

}
