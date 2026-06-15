<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Core\Database;

use PDO;
use TheSaiged\Core\Container;
use TheSaiged\Core\Database\Database;
use TheSaiged\Tests\TestCase;

/**
 * Integration tests for the Database wrapper against an in-memory SQLite.
 *
 * setUp() only primes a fresh PDO + the `items` test schema (via PDO
 * directly, not via Database — Database is the SUT). Each test calls
 * Container::get(Database::class) to get its own handle.
 */
final class DatabaseTest extends TestCase {

    function testFetchOneReturnsRow (): void {
        $db = Container::get(Database::class);
        $db->execute('INSERT INTO items (name) VALUES (:n)', [':n' => 'Foo']);
        $row = $db->fetchOne('SELECT * FROM items WHERE name = :n', [':n' => 'Foo']);
        $this->assertNotNull($row);
        $this->assertSame('Foo', $row['name']);
    }

    function testFetchOneReturnsNullForNoMatch (): void {
        $db = Container::get(Database::class);
        $this->assertNull($db->fetchOne('SELECT * FROM items WHERE id = ?', [99]));
    }

    function testFetchAllReturnsAllRows (): void {
        $db = Container::get(Database::class);
        $db->execute('INSERT INTO items (name) VALUES (:n)', [':n' => 'A']);
        $db->execute('INSERT INTO items (name) VALUES (:n)', [':n' => 'B']);
        $db->execute('INSERT INTO items (name) VALUES (:n)', [':n' => 'C']);

        $rows = $db->fetchAll('SELECT name FROM items ORDER BY name');

        $this->assertCount(3, $rows);
        $this->assertSame(['A', 'B', 'C'], array_column($rows, 'name'));
    }

    function testFetchAllReturnsEmptyArrayWhenNoRows (): void {
        $db = Container::get(Database::class);
        $this->assertSame([], $db->fetchAll('SELECT * FROM items'));
    }

    function testExecuteReturnsAffectedRowCount (): void {
        $db = Container::get(Database::class);
        $db->execute('INSERT INTO items (name) VALUES (:n)', [':n' => 'X']);
        $affected = $db->execute('DELETE FROM items WHERE name = :n', [':n' => 'X']);
        $this->assertSame(1, $affected);
    }

    function testRawExecutesMultiStatement (): void {
        $db = Container::get(Database::class);
        $db->raw('
            INSERT INTO items (name) VALUES ("A");
            INSERT INTO items (name) VALUES ("B");
        ');
        $this->assertCount(2, $db->fetchAll('SELECT * FROM items'));
    }

    function testLastInsertIdReturnsLatestId (): void {
        $db = Container::get(Database::class);
        $db->execute('INSERT INTO items (name) VALUES (:n)', [':n' => 'X']);
        $first = $db->lastInsertId();
        $db->execute('INSERT INTO items (name) VALUES (:n)', [':n' => 'Y']);
        $second = $db->lastInsertId();
        $this->assertGreaterThan($first, $second);
    }

    protected function setUp (): void {
        parent::setUp();
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
        Container::set(PDO::class, $pdo);
    }

}
