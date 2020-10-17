<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use ArgumentCountError;
use Closure;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Text;
use TypeError;

class CharacterDataSubstringDataTest extends NodeTestCase
{
    use DocumentGetter;

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithTooFewArgs(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertThrows(static function () use ($node): void {
            $node->substringData();
        }, ArgumentCountError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->substringData(0);
        }, ArgumentCountError::class);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithTooManyArgs(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('t', $node->substringData(0, 1));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithInvalidOffset(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertThrows(static function () use ($node): void {
            $node->substringData(5, 0);
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->substringData(6, 0);
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->substringData(-1, 0);
        }, IndexSizeError::class);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithInBoundOffset(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('t', $node->substringData(0, 1));
        $this->assertSame('e', $node->substringData(1, 1));
        $this->assertSame('s', $node->substringData(2, 1));
        $this->assertSame('t', $node->substringData(3, 1));
        $this->assertSame('', $node->substringData(4, 1));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithZeroCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('', $node->substringData(0, 0));
        $this->assertSame('', $node->substringData(1, 0));
        $this->assertSame('', $node->substringData(2, 0));
        $this->assertSame('', $node->substringData(3, 0));
        $this->assertSame('', $node->substringData(4, 0));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithVeryLargeOffset(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('t', $node->substringData(0x100000000 + 0, 1));
        $this->assertSame('e', $node->substringData(0x100000000 + 1, 1));
        $this->assertSame('s', $node->substringData(0x100000000 + 2, 1));
        $this->assertSame('t', $node->substringData(0x100000000 + 3, 1));
        $this->assertSame('', $node->substringData(0x100000000 + 4, 1));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithNegativeOffset(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('s', $node->substringData(-0x100000000 + 2, 1));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithStringOffset(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->expectException(TypeError::class);
        $this->assertSame('tes', $node->substringData('test', 3));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithInBoundsCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('t', $node->substringData(0, 1));
        $this->assertSame('te', $node->substringData(0, 2));
        $this->assertSame('tes', $node->substringData(0, 3));
        $this->assertSame('test', $node->substringData(0, 4));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithLargeCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('test', $node->substringData(0, 5));
        $this->assertSame('st', $node->substringData(2, 20));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithVeryLargeCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('s', $node->substringData(2, 0x100000000 + 1));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithNegativeCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame('test', $node->substringData(0, -1));
        $this->assertSame('te', $node->substringData(0, -0x100000000 + 2));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithNonAsciiData(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = "This is the character data test, other 資料，更多文字";
        $this->assertSame('char', $node->substringData(12, 4));
        $this->assertSame("資料", $node->substringData(39, 2));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testSubstringDataWithNonBMPData(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = "🌠 test 🌠 TEST";
        $this->assertSame("st 🌠 TE", $node->substringData(5, 8)); // Counting UTF-16 code units
    }

    public function nodesProvider(): array
    {
        $document = $this->getHTMLDocument();

        return [
            [static function () use ($document): Text {
                return $document->createTextNode('test');
            },],
            [static function () use ($document): Comment {
                return $document->createComment('test');
            }],
        ];
    }
}
