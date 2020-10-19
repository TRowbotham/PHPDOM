<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-28.htm
 */
class GetElementsByClassName28Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName("te xt");

        self::assertSame(3, $collection->length);
        self::assertSame('BODY', $collection[0]->parentNode->nodeName);
        self::assertSame('DIV', $collection[1]->parentNode->nodeName);
        self::assertSame('BODY', $collection[2]->parentNode->nodeName);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-28.html';
    }
}
