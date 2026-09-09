<?php declare(strict_types = 1);

namespace TheSaiged\Typography;

use TheSaiged\Core\Database\Database;

final readonly class TypographyRepository {

    function __construct (
        private Database $db,
    ) {}

    /** @return list<TypographyFace> */
    function listFaces (FontRole $role): array {
        $rows  = $this->db->fetchAll(
            'SELECT * FROM typography_faces WHERE role = :role ORDER BY id',
            [':role' => $role->value],
        );
        $faces = [];
        foreach ($rows as $row)
            $faces[] = TypographyFace::fromDbRow($row);
        return $faces;
    }

    function getFace (int $id): ?TypographyFace {
        $row = $this->db->fetchOne('SELECT * FROM typography_faces WHERE id = :id', [':id' => $id]);
        return $row !== null ? TypographyFace::fromDbRow($row) : null;
    }

    function addFace (FontRole $role, int $uploadId, int $weightMin, int $weightMax, FontStyle $style): int {
        $this->db->execute(
            'INSERT INTO typography_faces (role, upload_id, weight_min, weight_max, style)
             VALUES (:role, :uploadId, :weightMin, :weightMax, :style)',
            [
                ':role'      => $role->value,
                ':uploadId'  => $uploadId,
                ':weightMin' => $weightMin,
                ':weightMax' => $weightMax,
                ':style'     => $style->value,
            ],
        );
        return $this->db->lastInsertId();
    }

    function removeFace (int $id): bool {
        $affected = $this->db->execute('DELETE FROM typography_faces WHERE id = :id', [':id' => $id]);
        return $affected > 0;
    }

}
