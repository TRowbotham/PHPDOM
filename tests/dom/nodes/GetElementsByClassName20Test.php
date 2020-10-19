<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-20.htm
 */
class GetElementsByClassName20Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName('text');
        $newDiv = $document->createElement('div');
        $newDiv->setAttribute('class', 'text');
        $newDiv->innerHTML = 'text newDiv';
        $document->getElementsByTagName('table')[0]->tBodies[0]->rows[0]->cells[0]->appendChild($newDiv);

        self::assertSame(5, $collection->length);
        self::assertSame('DIV', $collection[0]->parentNode->nodeName);
        self::assertSame('DIV', $collection[1]->parentNode->nodeName);
        self::assertSame('TABLE', $collection[2]->parentNode->nodeName);
        self::assertSame('TD', $collection[3]->parentNode->nodeName);
        self::assertSame('TR', $collection[4]->parentNode->nodeName);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-20.html';
    }
}
