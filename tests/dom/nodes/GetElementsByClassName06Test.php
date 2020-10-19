<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-06.htm
 */
class GetElementsByClassName06Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName('a');
        $ele = $document->createElement('foo');
        $ele->setAttribute('class', 'a');
        $document->body->appendChild($ele);
        self::assertSame([$document->body, $ele], iterator_to_array($collection));
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-06.html';
    }
}
