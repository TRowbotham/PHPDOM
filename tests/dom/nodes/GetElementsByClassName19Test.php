<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-19.htm
 */
class GetElementsByClassName19Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName('text');
        self::assertSame(4, $collection->length);
        self::assertSame('DIV', $collection[0]->parentNode->nodeName);
        self::assertSame('DIV', $collection[1]->parentNode->nodeName);
        self::assertSame('TABLE', $collection[2]->parentNode->nodeName);
        self::assertSame('TR', $collection[3]->parentNode->nodeName);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-19.html';
    }
}
