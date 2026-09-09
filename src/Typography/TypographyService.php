<?php declare(strict_types = 1);

namespace TheSaiged\Typography;

use RuntimeException;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Uploads\UploadId;
use TheSaiged\Uploads\UploadKind;
use TheSaiged\Uploads\UploadService;

/**
 * Business operations for Typography faces. Composes TypographyRepository
 * (pure storage) with UploadService (to resolve/validate the referenced
 * upload) — controllers only ever talk to this, never to the repository
 * directly, so the resolved shape (face + its Upload) is assembled here.
 */
final readonly class TypographyService {

    /** CSS font-weight is valid over [1, 1000] per the CSS Fonts spec. */
    private const MIN_WEIGHT = 1;
    private const MAX_WEIGHT = 1000;

    function __construct (
        private TypographyRepository $repo,
        private UploadService        $uploads,
    ) {}

    /** @return array<string, list<array<string, mixed>>> keyed by FontRole::value */
    function get (): array {
        $result = [];
        foreach (FontRole::cases() as $role) {
            $result[$role->value] = array_map(
                fn (TypographyFace $face): array => $this->serialize($face),
                $this->repo->listFaces($role),
            );
        }
        return $result;
    }

    /**
     * @return array<string, mixed> the newly added face, serialized
     * @throws InvalidDataException unknown/non-font upload, or an invalid weight range
     */
    function addFace (FontRole $role, UploadId $uploadId, int $weightMin, int $weightMax, FontStyle $style): array {
        $upload = $this->uploads->get($uploadId)
            ?? throw new InvalidDataException('typography face', 'unknown upload id');
        if ($upload->kind !== UploadKind::Font)
            throw new InvalidDataException('typography face', 'upload is not a font');
        if ($weightMin < self::MIN_WEIGHT || $weightMax > self::MAX_WEIGHT || $weightMin > $weightMax)
            throw new InvalidDataException('typography face', 'invalid weight range');

        $id   = $this->repo->addFace($role, $uploadId->value, $weightMin, $weightMax, $style);
        $face = $this->repo->getFace($id)
            ?? throw new RuntimeException("Failed to read back typography face #$id");
        return $this->serialize($face);
    }

    /** @return bool false when the id doesn't match any row */
    function removeFace (int $id): bool {
        return $this->repo->removeFace($id);
    }

    /** @return array<string, mixed> */
    private function serialize (TypographyFace $face): array {
        return [
            'id'        => $face->id,
            'weightMin' => $face->weightMin,
            'weightMax' => $face->weightMax,
            'style'     => $face->style->value,
            'upload'    => $this->uploads->get(new UploadId($face->uploadId))?->toArray(),
        ];
    }

}
