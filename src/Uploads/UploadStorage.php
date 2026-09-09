<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use RuntimeException;
use TheSaiged\Core\Env;

/**
 * Filesystem layer for uploads. On-disk layout depends on kind — see
 * originalPath():
 *   images: data/uploads/images/{id}/original.{ext} (+ variant siblings)
 *   fonts:  data/uploads/fonts/{id}.{ext}            (never has siblings)
 *   other:  data/uploads/{id}/original.{ext}
 *
 * The base directory is configurable via UPLOADS_DIR env var (tests
 * point it at a temp dir); production uses the docker volume mount.
 */
final readonly class UploadStorage {

    private string $root;

    function __construct (
        private ImageProcessor $images,
    ) {
        $this->root = Env::optional('UPLOADS_DIR', '/var/www/the-saiged/data/uploads');
    }

    /**
     * Move an uploaded source file into permanent storage and create the
     * always-present 200×200 admin thumbnail variant (for images). Returns
     * the final dimensions for images, [null, null] for other kinds.
     *
     * @return array{?int, ?int}
     */
    function saveOriginal (Upload $upload, string $sourcePath): array {
        $target = $this->originalPath($upload);
        $this->ensureDir(dirname($target));

        if (!@rename($sourcePath, $target)) {
            // rename across filesystems (tmp → data volume) can fail; fall back to copy+unlink.
            if (!copy($sourcePath, $target))
                throw new RuntimeException("Failed to copy upload into $target");
            @unlink($sourcePath);
        }

        if ($upload->kind !== UploadKind::Image)
            return [null, null];

        $dims = $this->images->dimensions($target);
        $this->images->resizeCover($target, dirname($target) . '/thumb-200x200.webp', 200, 200);
        return $dims;
    }

    /**
     * Ensure a variant exists at the standard path. Idempotent: if the file
     * already exists it's reused; otherwise generated from the original.
     * Returns the relative URL for use in markup. Images only — callers
     * (UploadService::ensureVariant) already guard on kind.
     */
    function ensureVariant (Upload $upload, int $width, int $height): string {
        $original = $this->originalPath($upload);
        $spec     = "{$width}x{$height}-cover";
        $path     = dirname($original) . "/$spec.webp";
        if (!file_exists($path))
            $this->images->resizeCover($original, $path, $width, $height);
        return $upload->imageVariantUrl($spec);
    }

    /**
     * Delete everything on disk for an upload. Called by MediaController on
     * DELETE — the DB row is removed by the repository, this just nukes the
     * on-disk part. Images/other kinds live in their own per-id dir (safe to
     * remove wholesale); fonts are a single flat file among siblings, so
     * only that one file is unlinked.
     */
    function deleteAll (Upload $upload): void {
        $target = $this->originalPath($upload);

        if ($upload->kind === UploadKind::Font) {
            @unlink($target);
            return;
        }

        $dir = dirname($target);
        if (!is_dir($dir))
            return;
        $this->deleteRecursive($dir);
    }

    private function originalPath (Upload $upload): string {
        $id  = $upload->id;
        $ext = $upload->extension();
        return match ($upload->kind) {
            UploadKind::Image => "{$this->root}/images/$id/original.$ext",
            UploadKind::Font  => "{$this->root}/fonts/$id.$ext",
            default           => "{$this->root}/$id/original.$ext",
        };
    }

    private function ensureDir (string $path): void {
        if (is_dir($path))
            return;
        if (!@mkdir($path, 0775, true) && !is_dir($path))
            throw new RuntimeException("Failed to create directory: $path");
    }

    private function deleteRecursive (string $path): void {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..')
                continue;
            $full = "$path/$entry";
            is_dir($full) ? $this->deleteRecursive($full) : @unlink($full);
        }
        @rmdir($path);
    }

}
