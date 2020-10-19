<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-08.htm
 */
class GetElementsByClassName08Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        self::assertSame([$document->body], iterator_to_array($document->getElementsByClassName("a\fa")));
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-08.html';
    }
}
