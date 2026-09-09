<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell\Header;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Header\HeaderLink;
use TheSaiged\Tests\TestCase;

final class HeaderLinkTest extends TestCase {

    function testFromArrayHappyPath (): void {
        $link = HeaderLink::fromArray(['label' => 'Studio', 'href' => '/studio']);

        $this->assertSame('Studio', $link->label);
        $this->assertSame('/studio', $link->href);
    }

    function testToArrayIsInverseOfFromArray (): void {
        $data = ['label' => 'Studio', 'href' => '/studio'];

        $this->assertSame($data, HeaderLink::fromArray($data)->toArray());
    }

    #[TestWith([['href' => '/studio']])]
    #[TestWith([['label' => 'Studio']])]
    #[TestWith([['label' => 1, 'href' => '/studio']])]
    #[TestWith([['label' => 'Studio', 'href' => 1]])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        HeaderLink::fromArray($data);
    }

}
