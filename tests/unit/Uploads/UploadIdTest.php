<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Uploads;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Tests\TestCase;
use TheSaiged\Uploads\UploadId;

final class UploadIdTest extends TestCase {

    function testWrapsPositiveInteger (): void {
        $id = new UploadId(42);
        $this->assertSame(42, $id->value);
    }

    #[TestWith([0])]
    #[TestWith([-1])]
    function testRejectsZeroOrNegative (int $invalid): void {
        $this->expectException(InvalidDataException::class);
        new UploadId($invalid);
    }

}
