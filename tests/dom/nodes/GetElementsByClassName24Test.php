<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-24.htm
 */
class GetElementsByClassName24Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName("ΔЙあ叶葉");
        self::assertSame(2, $collection->length);
        self::assertSame('DIV', $collection[0]->parentNode->nodeName);
        self::assertSame('BODY', $collection[1]->parentNode->nodeName);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-24.html';
    }
}
