<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Admins;

use PDO;
use TheSaiged\Admins\AdminCreate;
use TheSaiged\Admins\AdminRepository;
use TheSaiged\Admins\AdminRole;
use TheSaiged\Core\Container;
use TheSaiged\Tests\TestCase;

final class AdminRepositoryIntegrationTest extends TestCase {

    function testFindByEmailReturnsNullWhenNoRow (): void {
        $this->assertNull(Container::get(AdminRepository::class)->findByEmail('a@x.com'));
    }

    function testInsertThenFindByEmailRoundTrips (): void {
        $repo = Container::get(AdminRepository::class);
        $repo->insert(new AdminCreate('a@x.com', AdminRole::Admin));

        $record = $repo->findByEmail('a@x.com');

        $this->assertNotNull($record);
        $this->assertSame('a@x.com', $record->email);
        $this->assertSame(AdminRole::Admin, $record->role);
    }

    function testListOrdersByCreatedAtThenId (): void {
        $repo = Container::get(AdminRepository::class);
        $repo->insert(new AdminCreate('first@x.com', AdminRole::Admin));
        $repo->insert(new AdminCreate('second@x.com', AdminRole::Editor));

        $emails = array_map(fn ($r) => $r->email, $repo->list());

        $this->assertSame(['first@x.com', 'second@x.com'], $emails);
    }

    function testCountAndCountByRole (): void {
        $repo = Container::get(AdminRepository::class);
        $repo->insert(new AdminCreate('a@x.com', AdminRole::Admin));
        $repo->insert(new AdminCreate('b@x.com', AdminRole::Editor));

        $this->assertSame(2, $repo->count());
        $this->assertSame(1, $repo->countByRole(AdminRole::Admin));
        $this->assertSame(1, $repo->countByRole(AdminRole::Editor));
    }

    function testUpdateRoleReturnsFalseForUnknownEmail (): void {
        $this->assertFalse(Container::get(AdminRepository::class)->updateRole('nobody@x.com', AdminRole::Admin));
    }

    function testUpdateRoleThenFindByEmailReflectsChange (): void {
        $repo = Container::get(AdminRepository::class);
        $repo->insert(new AdminCreate('a@x.com', AdminRole::Editor));
        $repo->updateRole('a@x.com', AdminRole::Admin);

        $this->assertSame(AdminRole::Admin, $repo->findByEmail('a@x.com')->role);
    }

    function testDeleteRemovesTheRow (): void {
        $repo = Container::get(AdminRepository::class);
        $repo->insert(new AdminCreate('a@x.com', AdminRole::Admin));

        $this->assertTrue($repo->delete('a@x.com'));
        $this->assertNull($repo->findByEmail('a@x.com'));
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
            CREATE TABLE admins (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                email      TEXT NOT NULL UNIQUE,
                role       TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        return $pdo;
    }

}
