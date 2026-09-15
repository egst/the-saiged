<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Search;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use TheSaiged\Core\Container;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Search\SuggestionsRepository;
use TheSaiged\Search\SuggestionsService;
use TheSaiged\Tests\TestCase;

final class SuggestionsServiceTest extends TestCase {

    function testListReturnsEmptyWhenNothingSaved (): void {
        $this->mockRepo(fn ($repo) => $repo->method('getData')->willReturn(null));

        $this->assertSame([], Container::get(SuggestionsService::class)->list());
    }

    function testListDecodesSavedJson (): void {
        $this->mockRepo(fn ($repo) => $repo->method('getData')->willReturn('["find an artist","view the studio"]'));

        $this->assertSame(
            ['find an artist', 'view the studio'],
            Container::get(SuggestionsService::class)->list(),
        );
    }

    function testListThrowsOnMalformedJson (): void {
        $this->mockRepo(fn ($repo) => $repo->method('getData')->willReturn('not-json{'));

        $this->expectException(InvalidDataException::class);
        Container::get(SuggestionsService::class)->list();
    }

    function testListThrowsWhenAnEntryIsNotAString (): void {
        $this->mockRepo(fn ($repo) => $repo->method('getData')->willReturn('["ok", 5]'));

        $this->expectException(InvalidDataException::class);
        Container::get(SuggestionsService::class)->list();
    }

    function testSaveTrimsAndPersistsAsJson (): void {
        $this->mockRepo(function ($repo) {
            $repo->expects($this->once())
                ->method('save')
                ->with(json_encode(['find an artist']));
        });

        Container::get(SuggestionsService::class)->save(['  find an artist  ']);
    }

    function testSaveThrowsOnEmptyEntry (): void {
        $this->mockRepo(fn ($repo) => $repo->expects($this->never())->method('save'));

        $this->expectException(InvalidDataException::class);
        Container::get(SuggestionsService::class)->save(['   ']);
    }

    function testSaveThrowsOnOverlongEntry (): void {
        $this->mockRepo(fn ($repo) => $repo->expects($this->never())->method('save'));

        $this->expectException(InvalidDataException::class);
        Container::get(SuggestionsService::class)->save([str_repeat('a', 201)]);
    }

    /** @param Closure(MockObject&SuggestionsRepository) $configuration */
    private function mockRepo (Closure $configuration): void {
        $repo = $this->createMock(SuggestionsRepository::class);
        $configuration($repo);
        Container::set(SuggestionsRepository::class, $repo);
    }

}
