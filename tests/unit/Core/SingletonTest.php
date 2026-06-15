<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core;

use TheSaiged\Core\Container;
use TheSaiged\Core\Singleton;
use TheSaiged\Tests\TestCase;

final class SingletonTest extends TestCase {

    function testGetReturnsAutowiredInstance (): void {
        $this->assertInstanceOf(SingletonFixture::class, SingletonFixture::get());
    }

    function testGetReturnsContainerOverride (): void {
        $instance = new SingletonFixture();
        Container::set(SingletonFixture::class, $instance);
        $this->assertSame($instance, SingletonFixture::get());
    }

}

final class SingletonFixture {
    use Singleton;
}
