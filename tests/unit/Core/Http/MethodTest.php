<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core\Http;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Tests\TestCase;
use TheSaiged\Core\Http\Method;
use ValueError;

final class MethodTest extends TestCase {

    #[TestWith(['GET',    Method::GET])]
    #[TestWith(['POST',   Method::POST])]
    #[TestWith(['PUT',    Method::PUT])]
    #[TestWith(['DELETE', Method::DELETE])]
    #[TestWith(['PATCH',  Method::PATCH])]
    function testTryFromReturnsCaseForKnownMethod (string $raw, Method $expected): void {
        $this->assertSame($expected, Method::tryFrom($raw));
    }

    #[TestWith(['PROPFIND'])]
    #[TestWith(['get'])]
    #[TestWith([''])]
    #[TestWith(['nonsense'])]
    function testTryFromReturnsNullForUnknown (string $raw): void {
        $this->assertNull(Method::tryFrom($raw));
    }

    function testFromThrowsForUnknownMethod (): void {
        $this->expectException(ValueError::class);
        Method::from('PROPFIND');
    }

}
