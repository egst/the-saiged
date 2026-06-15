<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core;

use RuntimeException;
use stdClass;
use TheSaiged\Core\Container;
use TheSaiged\Tests\TestCase;

final class ContainerTest extends TestCase {

    function testGetReturnsAutowiredInstance (): void {
        $instance = Container::get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $instance);
    }

    function testSetOverridesGet (): void {
        $instance = new stdClass();
        Container::set(stdClass::class, $instance);
        $this->assertSame($instance, Container::get(stdClass::class));
    }

    function testResetClearsOverrides (): void {
        $instance = new stdClass();
        Container::set(stdClass::class, $instance);
        Container::reset();
        $this->assertNotSame($instance, Container::get(stdClass::class));
    }

    function testGetThrowsOnTypeMismatch (): void {
        Container::set(stdClass::class, 'not an instance');
        $this->expectException(RuntimeException::class);
        Container::get(stdClass::class);
    }

}
