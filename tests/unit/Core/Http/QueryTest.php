<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core\Http;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Tests\TestCase;
use TheSaiged\Core\Http\Query;

final class QueryTest extends TestCase {

    function testGetReturnsNullForMissing (): void {
        $query = new Query();
        $this->assertNull($query->get('q'));
    }

    function testGetReturnsValue (): void {
        $query = new Query(['q' => 'hello']);
        $this->assertSame('hello', $query->get('q'));
    }

    /** @param array<string, mixed> $params */
    #[TestWith([['q' => 'hello'],       'q',    'hello'])]
    #[TestWith([['q' => ''],            'q',    ''])]
    #[TestWith([['tags' => ['a', 'b']], 'tags', null])]
    #[TestWith([[],                     'q',    null])]
    function testGetString (array $params, string $name, ?string $expected): void {
        $this->assertSame($expected, (new Query($params))->getString($name));
    }

    /** @param array<string, mixed> $params */
    #[TestWith([['page' => '2'],   'page', 2])]
    #[TestWith([['page' => '-10'], 'page', -10])]
    #[TestWith([['page' => '0'],   'page', 0])]
    #[TestWith([['page' => 'abc'], 'page', null])]
    #[TestWith([['page' => '1.5'], 'page', null])]
    #[TestWith([[],                'page', null])]
    function testGetInt (array $params, string $name, ?int $expected): void {
        $this->assertSame($expected, (new Query($params))->getInt($name));
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string>         $expected
     */
    #[TestWith([['tags' => 'php'],          'tags', ['php']])]
    #[TestWith([['tags' => ['php', 'web']], 'tags', ['php', 'web']])]
    #[TestWith([['tags' => ['php', 42]],    'tags', ['php']])]
    #[TestWith([['tags' => [1, 2, 3]],      'tags', []])]
    #[TestWith([[],                         'tags', []])]
    function testGetList (array $params, string $name, array $expected): void {
        $this->assertSame($expected, (new Query($params))->getList($name));
    }

    function testToArrayReturnsParams (): void {
        $params = ['q' => 'hello', 'page' => '2'];
        $this->assertSame($params, (new Query($params))->toArray());
    }

}
