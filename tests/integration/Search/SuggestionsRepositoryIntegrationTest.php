<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Integration\Search;

use PDO;
use TheSaiged\Core\Container;
use TheSaiged\Search\SuggestionsRepository;
use TheSaiged\Tests\TestCase;

final class SuggestionsRepositoryIntegrationTest extends TestCase {

    function testGetDataReturnsNullWhenNothingSavedYet (): void {
        $this->assertNull(Container::get(SuggestionsRepository::class)->getData());
    }

    function testSaveThenGetDataRoundTrips (): void {
        $repo = Container::get(SuggestionsRepository::class);
        $repo->save('["find an artist"]');

        $this->assertSame('["find an artist"]', $repo->getData());
    }

    function testSecondSaveUpdatesRatherThanCollides (): void {
        $repo = Container::get(SuggestionsRepository::class);
        $repo->save('["v1"]');
        $repo->save('["v2"]');

        $this->assertSame('["v2"]', $repo->getData());
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
            CREATE TABLE search_suggestions (
                id   INTEGER PRIMARY KEY,
                data TEXT NOT NULL
            )
        SQL);
        return $pdo;
    }

}
