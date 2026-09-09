<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Shell;

use TheSaiged\Core\Container;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Header\HeaderShell;
use TheSaiged\Shell\ShellRepository;
use TheSaiged\Shell\ShellService;
use TheSaiged\Tests\TestCase;

final class ShellServiceTest extends TestCase {

    function testGetReturnsDefaultWhenNothingSaved (): void {
        $repo = $this->createMock(ShellRepository::class);
        $repo->method('getData')->with('header')->willReturn(null);
        Container::set(ShellRepository::class, $repo);

        $header = Container::get(ShellService::class)->get('header');

        $this->assertInstanceOf(HeaderShell::class, $header);
        $this->assertSame([], $header->links);
        $this->assertNull($header->logoUploadId);
    }

    function testGetDecodesSavedRow (): void {
        $repo = $this->createMock(ShellRepository::class);
        $repo->method('getData')->with('header')
            ->willReturn('{"links":[{"label":"Studio","href":"/studio"}],"logoUploadId":5}');
        Container::set(ShellRepository::class, $repo);

        $header = Container::get(ShellService::class)->get('header');

        $this->assertInstanceOf(HeaderShell::class, $header);
        $this->assertCount(1, $header->links);
        $this->assertSame('Studio', $header->links[0]->label);
        $this->assertSame(5, $header->logoUploadId);
    }

    function testGetThrowsOnUnknownType (): void {
        Container::set(ShellRepository::class, $this->createMock(ShellRepository::class));

        $this->expectException(InvalidDataException::class);
        Container::get(ShellService::class)->get('sidebar');
    }

    function testGetThrowsOnMalformedStoredJson (): void {
        $repo = $this->createMock(ShellRepository::class);
        $repo->method('getData')->willReturn('not-json{');
        Container::set(ShellRepository::class, $repo);

        $this->expectException(InvalidDataException::class);
        Container::get(ShellService::class)->get('header');
    }

    function testSaveValidatesThenPersistsCanonicalJson (): void {
        $repo = $this->createMock(ShellRepository::class);
        $repo->expects($this->once())
            ->method('save')
            ->with('header', $this->callback(
                fn (string $json): bool => json_decode($json, true) === ['links' => [], 'logoUploadId' => null]
            ));
        Container::set(ShellRepository::class, $repo);

        Container::get(ShellService::class)->save('header', ['links' => [], 'logoUploadId' => null]);
    }

    function testSaveThrowsOnInvalidShapeWithoutTouchingRepository (): void {
        $repo = $this->createMock(ShellRepository::class);
        $repo->expects($this->never())->method('save');
        Container::set(ShellRepository::class, $repo);

        $this->expectException(InvalidDataException::class);
        Container::get(ShellService::class)->save('header', ['links' => 'not-a-list']);
    }

    function testSaveThrowsOnUnknownType (): void {
        Container::set(ShellRepository::class, $this->createMock(ShellRepository::class));

        $this->expectException(InvalidDataException::class);
        Container::get(ShellService::class)->save('sidebar', []);
    }

}
