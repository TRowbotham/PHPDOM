<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Rowbot\DOM\Comment;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\Text;
use TypeError;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/CharacterData-appendData.html
 */
class CharacterDataAppendDataTest extends TestCase
{
    #[DataProvider('nodeProvider')]
    public function testAppendDataBar(Closure $create, string $type): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->appendData('bar');
        $this->assertSame('testbar', $node->data);
    }

    #[DataProvider('nodeProvider')]
    public function testAppendDataEmptyString(Closure $create, string $type): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->appendData('');
        $this->assertSame('test', $node->data);
    }

    #[DataProvider('nodeProvider')]
    public function testAppendDataNonASCII(Closure $create, string $type): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->appendData(', append more 資料，測試資料');
        $this->assertSame('test, append more 資料，測試資料', $node->data);
        $this->assertSame(25, $node->length);
    }

    #[DataProvider('nodeProvider')]
    public function testAppendDataNull(Closure $create, string $type): void
    {
        $this->expectException(TypeError::class);
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->appendData(null);
        $this->assertSame('testnull', $node->data);
    }

    #[DataProvider('nodeProvider')]
    public function testAppendDataEmptyStringBar(Closure $create, string $type): void
    {
        $node = $create();

        $this->assertSame('test', $node->data);
        $node->appendData('', 'bar');
        $this->assertSame('test', $node->data);
    }

    public static function nodeProvider(): array
    {
        $document = new HTMLDocument();

        return [
            [static function () use ($document): Text {
                return $document->createTextNode('test');
            }, 'Text'],
            [static function () use ($document): Comment {
                return $document->createComment('test');
            }, 'Comment'],
        ];
    }
}
