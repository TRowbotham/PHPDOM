<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Closure;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Text;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/CharacterData-deleteData.html
 */
class CharacterDataDeleteDataTest extends NodeTestCase
{
    use DocumentGetter;

    /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataOutOfBounds(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertThrows(static function () use ($node): void {
            $node->deleteData(5, 10);
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->deleteData(5, 0);
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->deleteData(-1, 10);
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->deleteData(-1, 0);
        }, IndexSizeError::class);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataAtStart(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->deleteData(0, 2);
        $this->assertSame('st', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataAtEnd(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->deleteData(2, 10);
        $this->assertSame('te', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataInMiddle(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->deleteData(1, 1);
        $this->assertSame('tst', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataSmallNegativeCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->deleteData(2, -1);
        $this->assertSame('te', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataWithLargeNegativeCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);

        $node->deleteData(1, -0x100000000 + 2);
        $this->assertSame('tt', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataWithNonAsciiData(Closure $create): void
    {
        $node = $create();
        $node->data = "This is the character data test, append more 資料，更多測試資料";

        $node->deleteData(40, 5);
        $this->assertSame("This is the character data test, append 資料，更多測試資料", $node->data);
        $node->deleteData(45, 2);
        $this->assertSame("This is the character data test, append 資料，更多資料", $node->data);
    }

     /**
     * @dataProvider nodesProvider
     */
    public function testDeleteDataWithNonBMPData(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = "🌠 test 🌠 TEST";
        // $node->deleteData(5, 8); // Counting UTF-16 code units
        $node->deleteData(4, 7); // Counting UTF-8 code points
        $this->assertSame("🌠 teST", $node->data);
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
