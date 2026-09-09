<?php declare(strict_types = 1);

namespace TheSaiged\Typography;

use TheSaiged\Core\InvalidDataException;

/**
 * One uploaded font file assigned to a role, tagged with the weight range
 * and style it covers. A role can have any number of these — see
 * notes/ discussion: a static font gets weightMin === weightMax, a
 * variable font can span a real range (e.g. 100–900) in a single file.
 * No auto-detection from the file itself — the admin states what the
 * file covers, same as any type foundry's own font-face CSS would.
 */
final readonly class TypographyFace {

    function __construct (
        public int       $id,
        public FontRole  $role,
        public int       $uploadId,
        public int       $weightMin,
        public int       $weightMax,
        public FontStyle $style,
    ) {}

    /** @param array<string, mixed> $row */
    static function fromDbRow (array $row): self {
        $id         = $row['id']         ?? null;
        $roleRaw    = $row['role']       ?? null;
        $uploadId   = $row['upload_id']  ?? null;
        $weightMin  = $row['weight_min'] ?? null;
        $weightMax  = $row['weight_max'] ?? null;
        $styleRaw   = $row['style']      ?? null;

        if (!is_int($id) || !is_string($roleRaw) || !is_int($uploadId)
            || !is_int($weightMin) || !is_int($weightMax) || !is_string($styleRaw))
            throw new InvalidDataException('typography face row');

        $role  = FontRole::tryFrom($roleRaw)
            ?? throw new InvalidDataException('typography face row', "unknown role: $roleRaw");
        $style = FontStyle::tryFrom($styleRaw)
            ?? throw new InvalidDataException('typography face row', "unknown style: $styleRaw");

        return new self(
            id:        $id,
            role:      $role,
            uploadId:  $uploadId,
            weightMin: $weightMin,
            weightMax: $weightMax,
            style:     $style,
        );
    }

}
