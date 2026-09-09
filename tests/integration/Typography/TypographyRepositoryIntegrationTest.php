<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Typography;

use PDO;
use TheSaiged\Core\Container;
use TheSaiged\Tests\TestCase;
use TheSaiged\Typography\FontRole;
use TheSaiged\Typography\FontStyle;
use TheSaiged\Typography\TypographyRepository;

/**
 * TypographyRepository against in-memory SQLite. Covers the SQL contract:
 * insert returns id, listFaces scopes to one role and preserves insertion
 * order, removeFace is a boolean effective-row signal.
 */
final class TypographyRepositoryIntegrationTest extends TestCase {

    function testListFacesReturnsEmptyForRoleWithNothingSaved (): void {
        $this->assertSame([], Container::get(TypographyRepository::class)->listFaces(FontRole::Heading));
    }

    function testAddFaceReturnsIdAndPersistsAllFields (): void {
        $repo = Container::get(TypographyRepository::class);
        $id   = $repo->addFace(FontRole::Text, 9, 400, 700, FontStyle::Italic);

        $face = $repo->getFace($id);
        $this->assertNotNull($face);
        $this->assertSame(FontRole::Text,   $face->role);
        $this->assertSame(9,                $face->uploadId);
        $this->assertSame(400,              $face->weightMin);
        $this->assertSame(700,              $face->weightMax);
        $this->assertSame(FontStyle::Italic, $face->style);
    }

    function testListFacesScopesToRoleAndPreservesInsertionOrder (): void {
        $repo = Container::get(TypographyRepository::class);
        $repo->addFace(FontRole::Text,    1, 400, 400, FontStyle::Normal);
        $repo->addFace(FontRole::Heading, 2, 400, 400, FontStyle::Normal);
        $repo->addFace(FontRole::Text,    3, 700, 700, FontStyle::Normal);

        $textFaces = $repo->listFaces(FontRole::Text);
        $this->assertSame([1, 3], array_map(fn ($face) => $face->uploadId, $textFaces));
    }

    function testGetFaceReturnsNullForUnknown (): void {
        $this->assertNull(Container::get(TypographyRepository::class)->getFace(9999));
    }

    function testRemoveFaceReturnsTrueForExistingRow (): void {
        $repo = Container::get(TypographyRepository::class);
        $id   = $repo->addFace(FontRole::Text, 1, 400, 400, FontStyle::Normal);

        $this->assertTrue($repo->removeFace($id));
        $this->assertNull($repo->getFace($id));
    }

    function testRemoveFaceReturnsFalseForUnknownId (): void {
        $this->assertFalse(Container::get(TypographyRepository::class)->removeFace(9999));
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
            CREATE TABLE typography_faces (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                role       TEXT NOT NULL CHECK(role IN ('heading', 'text')),
                upload_id  INTEGER NOT NULL,
                weight_min INTEGER NOT NULL,
                weight_max INTEGER NOT NULL,
                style      TEXT NOT NULL CHECK(style IN ('normal', 'italic'))
            )
        SQL);
        return $pdo;
    }

}
