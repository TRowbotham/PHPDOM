<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Closure;
use Rowbot\DOM\Comment;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Text;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/CharacterData-data.html
 */
class CharacterDataDataTest extends NodeTestCase
{
    use DocumentGetter;

    /**
     * @dataProvider nodesProvider
     */
    public function testDataInitialValue(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $this->assertSame(4, $node->length);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testAssignDataNullValue(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = null;
        $this->assertSame('', $node->data);
        $this->assertSame(0, $node->length);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testAssignDataNumericZero(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = 0;
        $this->assertSame('0', $node->data);
        $this->assertSame(1, $node->length);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testAssignDataEmptyString(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = '';
        $this->assertSame('', $node->data);
        $this->assertSame(0, $node->length);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testAssignDataDoubleHyphen(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = '--';
        $this->assertSame('--', $node->data);
        $this->assertSame(2, $node->length);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testAssignDataNonAscii(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = '資料';
        $this->assertSame('資料', $node->data);
        $this->assertSame(2, $node->length);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testAssignDataNonBMP(Closure $create): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->data = "🌠 test 🌠 TEST";
        $this->assertSame("🌠 test 🌠 TEST", $node->data);
        // $this->assertSame(15, $node->length); // Counting UTF-16 code units
        self::assertSame(13, $node->length); // Counting UTF-8 code points
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
