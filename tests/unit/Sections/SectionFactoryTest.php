<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\SectionFactory;
use TheSaiged\Sections\Article\ArticleSection;
use TheSaiged\Tests\TestCase;

final class SectionFactoryTest extends TestCase {

    function testFromArrayBuildsArticleSection (): void {
        $section = SectionFactory::fromArray([
            'type' => 'article',
            'data' => ['content' => 'Hello'],
        ]);

        $this->assertInstanceOf(ArticleSection::class, $section);
        $this->assertSame('Hello', $section->content);
    }

    function testFromArrayThrowsOnUnknownType (): void {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('unknown type: nonexistent');
        SectionFactory::fromArray(['type' => 'nonexistent', 'data' => []]);
    }

    /** @param array<string, mixed> $row */
    #[TestWith([['data' => []]])]
    #[TestWith([['type' => 'article']])]
    #[TestWith([['type' => 1, 'data' => []]])]
    #[TestWith([['type' => 'article', 'data' => 'not-an-array']])]
    #[TestWith([[]])]
    function testFromArrayThrowsOnInvalidRow (array $row): void {
        $this->expectException(InvalidDataException::class);
        SectionFactory::fromArray($row);
    }

}
