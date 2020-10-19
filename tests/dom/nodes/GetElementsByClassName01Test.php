<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-01.htm
 */
class GetElementsByClassName01Test extends NodeTestCase
{
    use WindowTrait;

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(
            [$document->documentElement, $document->body],
            iterator_to_array($document->getElementsByClassName("\ta\n"))
        );
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-01.html';
    }
}
