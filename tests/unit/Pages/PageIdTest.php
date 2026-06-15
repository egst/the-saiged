<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Pages;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Pages\PageId;
use TheSaiged\Tests\TestCase;

final class PageIdTest extends TestCase {

    function testWrapsPositiveInteger (): void {
        $id = new PageId(42);
        $this->assertSame(42, $id->value);
    }

    #[TestWith([0])]
    #[TestWith([-1])]
    #[TestWith([-100])]
    function testRejectsZeroOrNegative (int $invalid): void {
        $this->expectException(InvalidDataException::class);
        new PageId($invalid);
    }

}
