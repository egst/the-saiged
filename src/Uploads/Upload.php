<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use TheSaiged\Core\InvalidDataException;

final readonly class Upload {

    function __construct (
        public int        $id,
        public string     $filename,
        public string     $mime,
        public UploadKind $kind,
        public int        $size,
        public ?int       $width,
        public ?int       $height,
        public string     $uploadedAt,
    ) {}

    /** @param array<string, mixed> $row */
    static function fromDbRow (array $row): self {
        $id         = $row['id']          ?? null;
        $filename   = $row['filename']    ?? null;
        $mime       = $row['mime']        ?? null;
        $kindRaw    = $row['kind']        ?? null;
        $size       = $row['size']        ?? null;
        $width      = $row['width']       ?? null;
        $height     = $row['height']      ?? null;
        $uploadedAt = $row['uploaded_at'] ?? null;

        if (!is_int($id) || !is_string($filename) || !is_string($mime)
            || !is_string($kindRaw) || !is_int($size) || !is_string($uploadedAt))
            throw new InvalidDataException('upload row');
        if ($width !== null && !is_int($width))
            throw new InvalidDataException('upload row', 'width must be int or null');
        if ($height !== null && !is_int($height))
            throw new InvalidDataException('upload row', 'height must be int or null');

        $kind = UploadKind::tryFrom($kindRaw)
            ?? throw new InvalidDataException('upload row', "unknown kind: $kindRaw");

        return new self(
            id:         $id,
            filename:   $filename,
            mime:       $mime,
            kind:       $kind,
            size:       $size,
            width:      $width,
            height:     $height,
            uploadedAt: $uploadedAt,
        );
    }

    /**
     * Public URL of the original file. Routed through nginx's whitelist alias
     * at /uploads/{id}/original.{ext} → data/uploads/{id}/original.{ext}.
     */
    function originalUrl (): string {
        return "/uploads/{$this->id}/original.{$this->extension()}";
    }

    /**
     * Public URL of a previously-generated variant. The caller is responsible
     * for having asked UploadStorage to produce it; this just builds the URL.
     *
     * Variants live next to `original.{ext}` in the same dir — the naming
     * pattern ({spec}.{ext}) is already unambiguous (original never carries
     * dimensions, variants always do), so no extra subdir is needed.
     */
    function variantUrl (string $spec, string $extension = 'webp'): string {
        return "/uploads/{$this->id}/{$spec}.{$extension}";
    }

    function extension (): string {
        $ext = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
        return $ext === '' ? 'bin' : $ext;
    }

    /**
     * Canonical API shape — what the admin endpoints return for an upload.
     * Self-serialization keeps controllers from needing to know which
     * fields are public, and centralizes the thumbnail-URL derivation.
     *
     * @return array<string, mixed>
     */
    function toArray (): array {
        return [
            'id'          => $this->id,
            'filename'    => $this->filename,
            'mime'        => $this->mime,
            'kind'        => $this->kind->value,
            'size'        => $this->size,
            'width'       => $this->width,
            'height'      => $this->height,
            'uploadedAt'  => $this->uploadedAt,
            'originalUrl' => $this->originalUrl(),
            'thumbUrl'    => $this->kind === UploadKind::Image
                ? $this->variantUrl('thumb-200x200')
                : null,
        ];
    }

}
