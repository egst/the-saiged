<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

use TheSaiged\Core\Database\Database;

final readonly class UploadRepository {

    function __construct (
        private Database $db,
    ) {}

    /** @return list<Upload> */
    function listUploads (): array {
        $rows    = $this->db->fetchAll('SELECT * FROM uploads ORDER BY uploaded_at DESC, id DESC');
        $uploads = [];
        foreach ($rows as $row)
            $uploads[] = Upload::fromDbRow($row);
        return $uploads;
    }

    function getById (int $id): ?Upload {
        $row = $this->db->fetchOne('SELECT * FROM uploads WHERE id = :id', [':id' => $id]);
        return $row !== null ? Upload::fromDbRow($row) : null;
    }

    function insert (
        string     $filename,
        string     $mime,
        UploadKind $kind,
        int        $size,
        ?int       $width,
        ?int       $height,
    ): int {
        $this->db->execute(
            'INSERT INTO uploads (filename, mime, kind, size, width, height)
             VALUES (:filename, :mime, :kind, :size, :width, :height)',
            [
                ':filename' => $filename,
                ':mime'     => $mime,
                ':kind'     => $kind->value,
                ':size'     => $size,
                ':width'    => $width,
                ':height'   => $height,
            ],
        );
        return $this->db->lastInsertId();
    }

    /**
     * Patch the image dimensions for an upload. Used after disk save, once
     * Imagick has read the actual pixel size of the stored file. Only
     * relevant for images; videos leave width/height NULL.
     */
    function setDimensions (int $id, int $width, int $height): void {
        $this->db->execute(
            'UPDATE uploads SET width = :width, height = :height WHERE id = :id',
            [':id' => $id, ':width' => $width, ':height' => $height],
        );
    }

    function delete (int $id): bool {
        $affected = $this->db->execute('DELETE FROM uploads WHERE id = :id', [':id' => $id]);
        return $affected > 0;
    }

}
