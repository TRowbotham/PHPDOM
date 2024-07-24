<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Closure;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Text;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/CharacterData-insertData.html
 */
class CharacterDataInsertDataTest extends NodeTestCase
{
    use DocumentGetter;

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataOutOfBounds(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertThrows(static function () use ($node): void {
            $node->insertData(5, 'x');
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->insertData(5, '');
        }, IndexSizeError::class);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataNegativeOutOfBounds(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertThrows(static function () use ($node): void {
            $node->insertData(-1, 'x');
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->insertData(-0x100000000 + 5, '');
        }, IndexSizeError::class);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataNegativeInOfBounds(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->insertData(-0x100000000 + 2, 'X');
        $this->assertSame('teXst', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataEmptyString(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->insertData(0, '');
        $this->assertSame('test', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataAtStart(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->insertData(0, 'X');
        $this->assertSame('Xtest', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataInMiddle(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->insertData(2, 'X');
        $this->assertSame('teXst', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataAtEnd(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->insertData(4, 'ing');
        $this->assertSame('testing', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataWithNonAsciiData(Closure $create): void
    {
        $node = $create();
        $node->data = "This is the character data, append more 資料，測試資料";
        $node->insertData(26, " test");

        $this->assertSame("This is the character data test, append more 資料，測試資料", $node->data);
        $node->insertData(48, "更多");
        $this->assertSame("This is the character data test, append more 資料，更多測試資料", $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testInsertDataWithNonBMPData(Closure $create): void
    {
        $node = $create();
        $node->data = "🌠 test 🌠 TEST";
        // $node->insertData(5, "--"); // Counting UTF-16 code units
        $node->insertData(4, "--"); // Counting UTF-8 code points

        $this->assertSame("🌠 te--st 🌠 TEST", $node->data);
    }

    public function nodesProvider(): array
    {
        $document = self::getHTMLDocument();

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
