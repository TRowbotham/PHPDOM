<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-03.htm
 */
class GetElementsByClassName03Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName('a');
        $document->body->className = 'b';
        self::assertSame([$document->documentElement], iterator_to_array($collection));
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-03.html';
    }
}
