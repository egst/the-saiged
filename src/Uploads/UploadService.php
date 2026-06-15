<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use Throwable;

/**
 * Business operations for uploaded media. Takes domain value objects
 * (UploadId, UploadInput) and returns Upload or null/bool — no HTTP,
 * no superglobals. Owns the atomic "DB row + file + dimensions"
 * sequence with rollback on disk failure.
 */
final readonly class UploadService {

    /**
     * Whitelist of accepted server-detected MIME types → kind. Client-sent
     * MIME values are NOT trusted; controllers pass us mime_content_type()
     * output via UploadInput::fromGlobals.
     */
    private const MIME_KINDS = [
        'image/jpeg' => UploadKind::Image,
        'image/png'  => UploadKind::Image,
        'image/gif'  => UploadKind::Image,
        'image/webp' => UploadKind::Image,
        'video/mp4'  => UploadKind::Video,
        'video/webm' => UploadKind::Video,
    ];

    function __construct (
        private UploadRepository $repo,
        private UploadStorage    $storage,
    ) {}

    /** @return list<Upload> */
    function list (): array {
        return $this->repo->listUploads();
    }

    function get (UploadId $id): ?Upload {
        return $this->repo->getById($id->value);
    }

    /**
     * Ensure a sized variant of an image upload exists on disk and return
     * its public URL. Idempotent — the variant is generated on first call
     * and reused on subsequent ones. Returns null if the upload doesn't
     * exist or isn't an image (only images can have variants).
     *
     * @throws \RuntimeException on Imagick / filesystem failures
     */
    function ensureVariant (UploadId $id, int $width, int $height): ?string {
        $upload = $this->repo->getById($id->value);
        if ($upload === null || $upload->kind !== UploadKind::Image)
            return null;
        return $this->storage->ensureVariant($upload, $width, $height);
    }

    /**
     * Persist a verified upload — insert row, move file into permanent
     * storage, generate admin thumbnail (for images), patch dimensions.
     * On any disk-side failure both the DB row and the on-disk dir are
     * rolled back.
     *
     * @throws UnsupportedMediaTypeException if the MIME isn't whitelisted
     * @throws \RuntimeException             on filesystem failures from
     *                                       UploadStorage (rare; e.g.
     *                                       permissions / disk full)
     */
    function create (UploadInput $input): Upload {
        $kind = self::MIME_KINDS[$input->detectedMime]
            ?? throw new UnsupportedMediaTypeException($input->detectedMime);

        $id = $this->repo->insert(
            $input->filename,
            $input->detectedMime,
            $kind,
            $input->size,
            null,
            null,
        );

        $upload = $this->repo->getById($id)
            ?? throw new \RuntimeException("Failed to read back upload #$id");

        try {
            [$width, $height] = $this->storage->saveOriginal($upload, $input->tempPath);
        } catch (Throwable $exception) {
            $this->repo->delete($id);
            $this->storage->deleteAll($id);
            throw $exception;
        }

        if ($width !== null && $height !== null)
            $this->repo->setDimensions($id, $width, $height);

        return $this->repo->getById($id)
            ?? throw new \RuntimeException("Failed to reload upload #$id");
    }

    /** @return bool false when the id doesn't match any row */
    function delete (UploadId $id): bool {
        if ($this->repo->getById($id->value) === null)
            return false;
        $this->storage->deleteAll($id->value);
        $this->repo->delete($id->value);
        return true;
    }

}
