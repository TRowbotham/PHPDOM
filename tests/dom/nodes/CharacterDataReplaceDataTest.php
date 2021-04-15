<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Closure;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Text;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/CharacterData-replaceData.html
 */
class CharacterDataReplaceDataTest extends NodeTestCase
{
    use DocumentGetter;

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataWithInvalidOffset(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertThrows(static function () use ($node): void {
            $node->replaceData(5, 1, 'x');
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->replaceData(5, 0, '');
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->replaceData(-1, 1, 'x');
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($node): void {
            $node->replaceData(-1, 0, '');
        }, IndexSizeError::class);
        $this->assertSame('test', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataWithClampedCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(2, 10, 'yo');
        $this->assertSame('teyo', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataWithNegativeClampedCount(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(2, -1, 'yo');
        $this->assertSame('teyo', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataBeforeStart(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(0, 0, 'yo');
        $this->assertSame('yotest', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataAtStartShorter(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(0, 2, 'y');
        $this->assertSame('yst', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataAtStartEqualLength(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(0, 2, 'yo');
        $this->assertSame('yost', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataAtStartLonger(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(0, 2, 'yoa');
        $this->assertSame('yoast', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataInMiddleShorter(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(1, 2, 'o');
        $this->assertSame('tot', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataInMiddleEqualLength(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(1, 2, 'yo');
        $this->assertSame('tyot', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataInMiddleLonger(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(1, 1, 'waddup');
        $this->assertSame('twaddupst', $node->data);
        $node->replaceData(1, 1, 'yup');
        $this->assertSame('tyupaddupst', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataAtEndShorter(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(1, 20, 'yo');
        $this->assertSame('tyo', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataAtEndEqualLength(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(2, 20, 'yo');
        $this->assertSame('teyo', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataAtEndLonger(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(4, 20, 'yo');
        $this->assertSame('testyo', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataWholeString(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(0, 4, 'quux');
        $this->assertSame('quux', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataWithEmptyString(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->replaceData(0, 4, '');
        $this->assertSame('', $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataWithNonAsciiData(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = "This is the character data test, append 資料，更多資料";
        $node->replaceData(33, 6, 'other');
        $this->assertSame("This is the character data test, other 資料，更多資料", $node->data);
        $node->replaceData(44, 2, "文字");
        $this->assertSame("This is the character data test, other 資料，更多文字", $node->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceDataWithNonBMPData(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = "🌠 test 🌠 TEST";
        $node->replaceData(5, 8, '--'); // Counting UTF-16 code units
        $this->assertSame("🌠 te--ST", $node->data);
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
