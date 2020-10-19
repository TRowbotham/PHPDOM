<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-25.htm
 */
class GetElementsByClassName25Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName("text ");
        self::assertSame(4, $collection->length);
        $boldText = $document->getElementsByTagName("b")[0];
        $document->getElementsByTagName("table")[0]->tBodies[0]->rows[0]->cells[0]->appendChild($boldText);
        self::assertSame(4, $collection->length);
        self::assertSame('DIV', $collection[0]->parentNode->nodeName);
        self::assertSame('TABLE', $collection[1]->parentNode->nodeName);
        self::assertSame('TD', $collection[2]->parentNode->nodeName);
        self::assertSame('TR', $collection[3]->parentNode->nodeName);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-25.html';
    }
}
