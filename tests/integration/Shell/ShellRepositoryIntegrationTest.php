<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Shell;

use PDO;
use TheSaiged\Core\Container;
use TheSaiged\Shell\ShellRepository;
use TheSaiged\Tests\TestCase;

/**
 * Exercises ShellRepository against a real (SQLite in-memory) database —
 * same rationale as PageRepositoryIntegrationTest. The interesting case
 * here is save()'s upsert: first save inserts, second save on the same
 * type updates the existing row rather than colliding on the PK.
 */
final class ShellRepositoryIntegrationTest extends TestCase {

    function testGetDataReturnsNullWhenNothingSavedYet (): void {
        $this->assertNull(Container::get(ShellRepository::class)->getData('header'));
    }

    function testSaveThenGetDataRoundTrips (): void {
        $repo = Container::get(ShellRepository::class);
        $repo->save('header', '{"links":[]}');

        $this->assertSame('{"links":[]}', $repo->getData('header'));
    }

    function testSecondSaveOnSameTypeUpdatesRatherThanCollides (): void {
        $repo = Container::get(ShellRepository::class);
        $repo->save('header', '{"v":1}');
        $repo->save('header', '{"v":2}');

        $this->assertSame('{"v":2}', $repo->getData('header'));
    }

    function testEachTypeHasItsOwnRow (): void {
        $repo = Container::get(ShellRepository::class);
        $repo->save('header', '{"who":"header"}');
        $repo->save('footer', '{"who":"footer"}');

        $this->assertSame('{"who":"header"}', $repo->getData('header'));
        $this->assertSame('{"who":"footer"}', $repo->getData('footer'));
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
            CREATE TABLE shell (
                type       TEXT PRIMARY KEY,
                data       TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        return $pdo;
    }

}
