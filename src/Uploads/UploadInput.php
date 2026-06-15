<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use TheSaiged\Core\InvalidDataException;

/**
 * Validated upload source — the contract between MediaController and
 * UploadService. The service never touches $_FILES; controllers call
 * UploadInput::fromGlobals() which performs all the multipart-shape
 * checks (UPLOAD_ERR_*, is_uploaded_file, mime detection) and either
 * returns a clean value object or throws InvalidDataException.
 */
final readonly class UploadInput {

    function __construct (
        public string $tempPath,
        public string $filename,
        public int    $size,
        public string $detectedMime,
    ) {}

    /**
     * Read PHP's $_FILES['file'] entry into a UploadInput.
     *
     * @throws InvalidDataException for any malformed / missing / oversized
     *                              upload (size limit, missing tmp_name,
     *                              undetectable MIME, etc.)
     */
    static function fromGlobals (): self {
        $file = $_FILES['file'] ?? null;
        if (!is_array($file))
            throw new InvalidDataException('upload', 'expected multipart upload with field "file"');

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE)
            throw new InvalidDataException('upload', 'file exceeds upload size limit');
        if ($error !== UPLOAD_ERR_OK)
            throw new InvalidDataException('upload', 'upload failed (error code ' . (int) $error . ')');

        $tmpName  = $file['tmp_name'] ?? '';
        $filename = $file['name']     ?? '';
        $size     = $file['size']     ?? 0;
        if (!is_string($tmpName) || $tmpName === '' || !is_uploaded_file($tmpName))
            throw new InvalidDataException('upload', 'no valid uploaded file');
        if (!is_string($filename) || $filename === '')
            throw new InvalidDataException('upload', 'missing filename');
        if (!is_int($size) || $size <= 0)
            throw new InvalidDataException('upload', 'empty upload');

        $mime = mime_content_type($tmpName);
        if ($mime === false)
            throw new InvalidDataException('upload', 'could not detect file type');

        return new self(
            tempPath:     $tmpName,
            filename:     basename($filename),
            size:         $size,
            detectedMime: $mime,
        );
    }

}
