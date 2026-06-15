<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use Imagick;
use RuntimeException;

/**
 * Thin wrapper around Imagick for the resize / cover-crop operation we use
 * for variant generation. Kept narrow on purpose — the rest of the upload
 * pipeline doesn't need to know about Imagick directly, making it
 * straightforward to swap (or fake in tests) later.
 */
final readonly class ImageProcessor {

    /**
     * Read the image's pixel dimensions without loading the full bitmap into
     * memory beyond what Imagick needs for the header.
     *
     * @return array{int, int}  [width, height]
     */
    function dimensions (string $path): array {
        $img = new Imagick();
        try {
            $img->pingImage($path);
            return [$img->getImageWidth(), $img->getImageHeight()];
        } finally {
            $img->clear();
        }
    }

    /**
     * Resize the source image to fill the target dimensions exactly,
     * cropping overflow (`cover` fit). Written as WebP for size + quality.
     */
    function resizeCover (string $sourcePath, string $targetPath, int $width, int $height): void {
        $img = new Imagick($sourcePath);
        try {
            // cropThumbnailImage scales the image so the smaller dimension
            // matches, then crops to the requested size — exactly the
            // "cover" semantic. Strips metadata to keep output small.
            $img->setImageBackgroundColor('white');
            $img = $img->flattenImages();
            $img->stripImage();
            $img->cropThumbnailImage($width, $height);
            $img->setImageFormat('webp');
            $img->setImageCompressionQuality(85);
            if (!$img->writeImage($targetPath))
                throw new RuntimeException("Failed to write variant: $targetPath");
        } finally {
            $img->clear();
        }
    }

}
