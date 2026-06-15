<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use RuntimeException;
use TheSaiged\Core\Env;

/**
 * Filesystem layer for uploads. Owns the on-disk layout under
 * data/uploads/{id}/ — the original file at original.{ext} and any
 * derived variants under variants/{spec}.{ext}.
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
     * On-disk layout (flat — variants live next to the original because the
     * naming patterns never collide):
     *   data/uploads/{id}/original.{ext}
     *   data/uploads/{id}/thumb-200x200.webp
     *   data/uploads/{id}/{w}x{h}-cover.webp
     *
     * @return array{?int, ?int}
     */
    function saveOriginal (Upload $upload, string $sourcePath): array {
        $dir = $this->dir($upload->id);
        $this->ensureDir($dir);

        $target = "$dir/original.{$upload->extension()}";
        if (!@rename($sourcePath, $target)) {
            // rename across filesystems (tmp → data volume) can fail; fall back to copy+unlink.
            if (!copy($sourcePath, $target))
                throw new RuntimeException("Failed to copy upload into $target");
            @unlink($sourcePath);
        }

        if ($upload->kind !== UploadKind::Image)
            return [null, null];

        $dims = $this->images->dimensions($target);
        $this->images->resizeCover($target, "$dir/thumb-200x200.webp", 200, 200);
        return $dims;
    }

    /**
     * Ensure a variant exists at the standard path. Idempotent: if the file
     * already exists it's reused; otherwise generated from the original.
     * Returns the relative URL for use in markup.
     */
    function ensureVariant (Upload $upload, int $width, int $height): string {
        $dir    = $this->dir($upload->id);
        $spec   = "{$width}x{$height}-cover";
        $path   = "$dir/$spec.webp";
        if (!file_exists($path))
            $this->images->resizeCover("$dir/original.{$upload->extension()}", $path, $width, $height);
        return $upload->variantUrl($spec);
    }

    /**
     * Delete the upload's entire directory (original + all variants).
     * Called by MediaController on DELETE — DB row is removed by the
     * repository, this just nukes the on-disk part.
     */
    function deleteAll (int $id): void {
        $dir = $this->dir($id);
        if (!is_dir($dir))
            return;
        $this->deleteRecursive($dir);
    }

    private function dir (int $id): string {
        return "{$this->root}/$id";
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
