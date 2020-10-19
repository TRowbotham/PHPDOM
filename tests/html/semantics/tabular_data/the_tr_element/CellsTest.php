<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tr_element;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tr-element/cells.html
 */
class CellsTest extends TestCase
{
    use WindowTrait;

    public function testCells(): void
    {
        $document = self::getWindow()->document;
        $tr = $document->getElementById('testTr');

        $tr->insertBefore($document->createElementNS('foo', 'td'), $tr->children[1]);
        self::assertSame(
            [$tr->children[0], $tr->children[2], $tr->children[3]],
            iterator_to_array($tr->cells)
        );
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'cells.html';
    }
}
