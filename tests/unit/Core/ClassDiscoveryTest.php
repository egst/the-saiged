<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core;

use TheSaiged\Core\ClassDiscovery;
use TheSaiged\Tests\TestCase;
use TheSaiged\Tests\Unit\Core\Fixtures\Discovery\Base;
use TheSaiged\Tests\Unit\Core\Fixtures\Discovery\ChildA;
use TheSaiged\Tests\Unit\Core\Fixtures\Discovery\ChildB;

final class ClassDiscoveryTest extends TestCase {

    private const FIXTURES_DIR  = __DIR__ . '/Fixtures/Discovery';
    private const FIXTURES_NS   = 'TheSaiged\\Tests\\Unit\\Core\\Fixtures\\Discovery';

    function testReturnsOnlySubclassesOfBase (): void {
        $found = ClassDiscovery::inDirectory(self::FIXTURES_DIR, self::FIXTURES_NS, Base::class);

        sort($found);
        $this->assertSame([ChildA::class, ChildB::class], $found);
    }

    function testExcludesBaseClassItself (): void {
        $found = ClassDiscovery::inDirectory(self::FIXTURES_DIR, self::FIXTURES_NS, Base::class);

        $this->assertNotContains(Base::class, $found);
    }

    function testExcludesUnrelatedClasses (): void {
        $found = ClassDiscovery::inDirectory(self::FIXTURES_DIR, self::FIXTURES_NS, Base::class);

        $this->assertNotContains(
            'TheSaiged\\Tests\\Unit\\Core\\Fixtures\\Discovery\\Unrelated',
            $found,
        );
    }

    function testReturnsEmptyListForUnmatchedBase (): void {
        $found = ClassDiscovery::inDirectory(self::FIXTURES_DIR, self::FIXTURES_NS, self::class);

        $this->assertSame([], $found);
    }

}
