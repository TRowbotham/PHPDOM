<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-21.htm
 */
class GetElementsByClassName21Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName('text1');

        self::assertSame(1, $collection->length);
        $document->getElementsByTagName('table')[0]->deleteRow(1);
        self::assertSame(0, $collection->length);
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-21.html';
    }
}
