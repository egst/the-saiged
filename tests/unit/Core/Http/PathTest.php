<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core\Http;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Tests\TestCase;
use TheSaiged\Core\Http\Path;

final class PathTest extends TestCase {

    function testStoresValue (): void {
        $path = new Path('/users/42');
        $this->assertSame('/users/42', $path->value);
    }

    function testToString (): void {
        $path = new Path('/users/42');
        $this->assertSame('/users/42', (string) $path);
    }

    function testGetReturnsNullForMissingParam (): void {
        $path = new Path('/users/42');
        $this->assertNull($path->get('id'));
    }

    function testGetReturnsCapturedParam (): void {
        $path = (new Path('/users/42'))->withParams(['id' => '42']);
        $this->assertSame('42', $path->get('id'));
    }

    function testWithParamsIsImmutable (): void {
        $original = new Path('/users/42');
        $with     = $original->withParams(['id' => '42']);
        $this->assertNull($original->get('id'));
        $this->assertSame('42', $with->get('id'));
    }

    #[TestWith(['42',  42])]
    #[TestWith(['-10', -10])]
    #[TestWith(['0',   0])]
    #[TestWith(['abc', null])]
    #[TestWith(['1.5', null])]
    #[TestWith(['',    null])]
    function testGetInt (string $raw, ?int $expected): void {
        $path = (new Path('/x'))->withParams(['n' => $raw]);
        $this->assertSame($expected, $path->getInt('n'));
    }

    function testGetIntReturnsNullForMissing (): void {
        $path = new Path('/users/42');
        $this->assertNull($path->getInt('id'));
    }

}
