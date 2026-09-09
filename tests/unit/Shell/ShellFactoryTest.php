<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Footer\FooterShell;
use TheSaiged\Shell\Header\HeaderShell;
use TheSaiged\Shell\ShellFactory;
use TheSaiged\Tests\TestCase;

final class ShellFactoryTest extends TestCase {

    function testClassForResolvesKnownTypes (): void {
        $this->assertSame(HeaderShell::class, ShellFactory::classFor('header'));
        $this->assertSame(FooterShell::class, ShellFactory::classFor('footer'));
    }

    function testClassForThrowsOnUnknownType (): void {
        $this->expectException(InvalidDataException::class);
        ShellFactory::classFor('sidebar');
    }

}
