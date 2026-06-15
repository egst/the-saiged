<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\TagList\TagListSection;
use TheSaiged\Tests\TestCase;

final class TagListTest extends TestCase {

    function testTypeReturnsTagList (): void {
        $this->assertSame('tag-list', TagListSection::type());
    }

    function testFromArrayHappyPath (): void {
        $section = TagListSection::fromArray([
            'heading' => 'Market Access',
            'body'    => 'Through selected relationships.',
            'tags'    => 'Post-War, Contemporary, Emerging',
        ]);

        $this->assertSame('Market Access',              $section->heading);
        $this->assertSame('Through selected relationships.', $section->body);
        $this->assertSame('Post-War, Contemporary, Emerging', $section->tags);
    }

    /** @param array<string, mixed> $data */
    #[TestWith([[]])]
    #[TestWith([['body' => 'b', 'tags' => 't']])]
    #[TestWith([['heading' => 'h', 'tags' => 't']])]
    #[TestWith([['heading' => 'h', 'body' => 'b']])]
    #[TestWith([['heading' => 1, 'body' => 'b', 'tags' => 't']])]
    function testFromArrayThrowsOnInvalidShape (array $data): void {
        $this->expectException(InvalidDataException::class);
        TagListSection::fromArray($data);
    }

    function testToArrayRoundtrips (): void {
        $data    = ['heading' => 'H', 'body' => 'B', 'tags' => 'A, B, C'];
        $section = TagListSection::fromArray($data);

        $this->assertSame($data, $section->toArray());
    }

    function testRenderEscapesContent (): void {
        $section = new TagListSection('<h>', '<b>', '<t>');

        $html = $section->render();

        $this->assertStringContainsString('&lt;h&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringContainsString('&lt;t&gt;', $html);
        $this->assertStringNotContainsString('<h>', $html);
    }

    function testRenderSplitsTagsOnComma (): void {
        $section = new TagListSection('H', 'B', 'Post-War, Contemporary, Emerging');

        $html = $section->render();

        $this->assertSame(3, substr_count($html, '<span class="tag-list-tag">'));
        $this->assertStringContainsString('Post-War',    $html);
        $this->assertStringContainsString('Contemporary', $html);
        $this->assertStringContainsString('Emerging',     $html);
    }

    function testRenderIgnoresEmptyTags (): void {
        $section = new TagListSection('H', 'B', 'A, , B');

        $this->assertSame(2, substr_count($section->render(), '<span class="tag-list-tag">'));
    }

    function testRenderEmptyTagsProducesNoChips (): void {
        $section = new TagListSection('H', 'B', '');

        $this->assertSame(0, substr_count($section->render(), '<span class="tag-list-tag">'));
    }

}
