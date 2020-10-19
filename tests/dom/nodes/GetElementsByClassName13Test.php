<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-13.htm
 */
class GetElementsByClassName13Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->body->getElementsByClassName("a");
        $ele = $document->createElement('x-y-z');
        $ele->className = 'a';
        $document->body->appendChild($ele);
        self::assertSame([$ele], iterator_to_array($collection));
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-13.html';
    }
}
