<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Uploads;

use PDO;
use TheSaiged\Core\Container;
use TheSaiged\Tests\TestCase;
use TheSaiged\Uploads\UploadKind;
use TheSaiged\Uploads\UploadRepository;

/**
 * UploadRepository against in-memory SQLite. Covers the SQL contract:
 * insert returns id, listUploads is sorted newest-first by uploaded_at,
 * setDimensions patches only the dimension fields, delete is a boolean
 * effective-row signal.
 */
final class UploadRepositoryIntegrationTest extends TestCase {

    function testInsertReturnsIdAndPersistsAllFields (): void {
        $repo = Container::get(UploadRepository::class);
        $id   = $repo->insert('photo.jpg', 'image/jpeg', UploadKind::Image, 1234, 800, 600);

        $upload = $repo->getById($id);
        $this->assertNotNull($upload);
        $this->assertSame('photo.jpg',     $upload->filename);
        $this->assertSame('image/jpeg',    $upload->mime);
        $this->assertSame(UploadKind::Image, $upload->kind);
        $this->assertSame(1234,            $upload->size);
        $this->assertSame(800,             $upload->width);
        $this->assertSame(600,             $upload->height);
    }

    function testInsertAcceptsNullDimensionsForVideo (): void {
        $repo = Container::get(UploadRepository::class);
        $id   = $repo->insert('clip.mp4', 'video/mp4', UploadKind::Video, 50_000, null, null);

        $upload = $repo->getById($id);
        $this->assertNotNull($upload);
        $this->assertNull($upload->width);
        $this->assertNull($upload->height);
        $this->assertSame(UploadKind::Video, $upload->kind);
    }

    function testGetByIdReturnsNullForUnknown (): void {
        $this->assertNull(Container::get(UploadRepository::class)->getById(9999));
    }

    function testListUploadsOrdersByUploadedAtDescending (): void {
        $repo = Container::get(UploadRepository::class);
        $repo->insert('a.jpg', 'image/jpeg', UploadKind::Image, 1, null, null);
        $repo->insert('b.jpg', 'image/jpeg', UploadKind::Image, 1, null, null);
        $repo->insert('c.jpg', 'image/jpeg', UploadKind::Image, 1, null, null);

        $list = $repo->listUploads();

        // Same uploaded_at second → ties broken by id DESC (newest insert first).
        $this->assertSame(['c.jpg', 'b.jpg', 'a.jpg'], array_map(
            fn ($upload) => $upload->filename,
            $list,
        ));
    }

    function testListUploadsReturnsEmptyArrayForEmptyTable (): void {
        $this->assertSame([], Container::get(UploadRepository::class)->listUploads());
    }

    function testSetDimensionsPatchesOnlyWidthAndHeight (): void {
        $repo = Container::get(UploadRepository::class);
        $id   = $repo->insert('photo.jpg', 'image/jpeg', UploadKind::Image, 1234, null, null);

        $repo->setDimensions($id, 1920, 1080);

        $upload = $repo->getById($id);
        $this->assertNotNull($upload);
        $this->assertSame(1920, $upload->width);
        $this->assertSame(1080, $upload->height);
        $this->assertSame('photo.jpg', $upload->filename, 'filename untouched');
    }

    function testDeleteReturnsTrueForExistingRow (): void {
        $repo = Container::get(UploadRepository::class);
        $id   = $repo->insert('x.jpg', 'image/jpeg', UploadKind::Image, 1, null, null);

        $this->assertTrue($repo->delete($id));
        $this->assertNull($repo->getById($id));
    }

    function testDeleteReturnsFalseForUnknownId (): void {
        $this->assertFalse(Container::get(UploadRepository::class)->delete(9999));
    }

    protected function setUp (): void {
        parent::setUp();
        Container::set(PDO::class, self::makeDb());
    }

    private static function makeDb (): PDO {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec(<<<'SQL'
            CREATE TABLE uploads (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                filename    TEXT NOT NULL,
                mime        TEXT NOT NULL,
                kind        TEXT NOT NULL CHECK(kind IN ('image', 'video')),
                size        INTEGER NOT NULL,
                width       INTEGER NULL,
                height      INTEGER NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        return $pdo;
    }

}
