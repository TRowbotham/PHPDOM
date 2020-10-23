<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/insert_adjacent_html.html
 */
class InsertAdjacentHTMLTest extends TestCase
{
    use InsertAdjacentHTMLTrait;

    private $script_ran = false;

    /**
     * @dataProvider nodeProvider1
     */
    public function testBeforebegin(Node $node): void
    {
        $this->script_ran = false;
        $node->insertAdjacentHTML('beforeBegin', "\u{003C}script>script_ran = true;\u{003C}/script><i></i>");
        self::assertSame('i', $node->previousSibling->localName);
        self::assertSame('script', $node->previousSibling->previousSibling->localName);
        self::assertFalse($this->script_ran);
    }

    /**
     * @dataProvider nodeProvider1
     */
    public function testAfterbegin(Node $node): void
    {
        $this->script_ran = false;
        $node->insertAdjacentHTML('Afterbegin', "<b></b>\u{003C}script>script_ran = true;\u{003C}/script>");
        self::assertSame('b', $node->firstChild->localName);
        self::assertSame('script', $node->firstChild->nextSibling->localName);
        self::assertFalse($this->script_ran);
    }

    /**
     * @dataProvider nodeProvider1
     */
    public function testBeforeEnd(Node $node): void
    {
        $this->script_ran = false;
        $node->insertAdjacentHTML('BeforeEnd', "\u{003C}script>script_ran = true;\u{003C}/script><u></u>");
        self::assertSame('u', $node->lastChild->localName);
        self::assertSame('script', $node->lastChild->previousSibling->localName);
        self::assertFalse($this->script_ran);
    }

    /**
     * @dataProvider nodeProvider1
     */
    public function testAfterend(Node $node): void
    {
        $this->script_ran = false;
        $node->insertAdjacentHTML('afterend', "<a></a>\u{003C}script>script_ran = true;\u{003C}/script>");
        self::assertSame('a', $node->nextSibling->localName);
        self::assertSame('script', $node->nextSibling->nextSibling->localName);
        self::assertFalse($this->script_ran);
    }

    public function testShouldThrowWhenInsertingWithInvalidPositionString(): void
    {
        $content = self::getWindow()->document->getElementById('content');

        $this->assertThrows(static function () use ($content): void {
            $content->insertAdjacentHTML('bar', '');
        }, SyntaxError::class);
        $this->assertThrows(static function () use ($content): void {
            $content->insertAdjacentHTML('beforebegİn', '');
        }, SyntaxError::class);
        $this->assertThrows(static function () use ($content): void {
            $content->insertAdjacentHTML('beforebegın', 'foo');
        }, SyntaxError::class);
    }

    public function testInsertingAfterbeginAndBeforeendShouldOrderThingsCorrectly(): array
    {
        $document = self::getWindow()->document;
        $content = $document->getElementById('content');
        $parentElement = $document->createElement('div');
        $child = $document->createElement('div');
        $child->id = 'child';
        $child->insertAdjacentHTML('afterBegin', 'foo');
        $child->insertAdjacentHTML('beforeend', 'bar');
        self::assertSame('foobar', $child->textContent);
        $parentElement->appendChild($child);

        return [$document, $content, $parentElement];
    }

    /**
     * @depends testInsertingAfterbeginAndBeforeendShouldOrderThingsCorrectly
     */
    public function test2(array $nodes): void
    {
        [$document, $content, $parentElement] = $nodes;
        $this->script_ran = false;
        $content->appendChild($parentElement); // must not run scripts
        self::assertFalse($this->script_ran);
    }

    /**
     * @dataProvider nodeProvider2
     */
    public function test3(Node $node): void
    {
        $this->testBeforebegin($node);
        $this->testAfterbegin($node);
        $this->testBeforeEnd($node);
        $this->testAfterend($node);
    }

    public function testInsertingKidsOfTheHtmlElementShouldNotDoWeirdThingsWithImpliedBodyHeadTags(): void
    {
        $document = self::getWindow()->document;
        $document->body->insertAdjacentHTML('afterend', '<p>');
        $document->head->insertAdjacentHTML('beforebegin', '<p>');
        self::assertSame(1, $document->getElementsByTagName('head')->length);
        self::assertSame(1, $document->getElementsByTagName('body')->length);
    }

    public function nodeProvider1(): array
    {
        $content = self::getWindow()->document->getElementById('content');

        return [[$content], [$content]];
    }

    public function nodeProvider2(): array
    {
        $content2 = self::getWindow()->document->getElementById('content');

        return [[$content2], [$content2]];
    }

    public static function getDocumentName(): string
    {
        return 'insert_adjacent_html.html';
    }
}
