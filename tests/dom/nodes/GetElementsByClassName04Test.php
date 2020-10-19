<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-04.htm
 */
class GetElementsByClassName04Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName('a');
        $document->body->className .= "\tb";
        self::assertSame([$document->documentElement, $document->body], iterator_to_array($collection));
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-04.html';
    }
}
