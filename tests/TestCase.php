<?php declare(strict_types = 1);

namespace TheSaiged\Tests;

use TheSaiged\Core\Container;

abstract class TestCase extends \PHPUnit\Framework\TestCase {

    protected function setUp (): void {
        parent::setUp();
        Container::reset();
    }

}
