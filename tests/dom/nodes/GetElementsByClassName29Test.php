<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-29.htm
 */
class GetElementsByClassName29Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByTagName("table")[0]->getElementsByClassName("te xt");

        self::assertSame(1, $collection->length);
        self::assertSame('TR', $collection[0]->parentNode->nodeName);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-29.html';
    }
}
