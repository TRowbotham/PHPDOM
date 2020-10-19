<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\attributes_common_to_td_and_th_elements;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

use function method_exists;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/attributes-common-to-td-and-th-elements/cellIndex.html
 */
class CellIndexTest extends TestCase
{
    use DocumentGetter;

    public function testCellIndexShouldExist(): void
    {
        self::markTestSkipped('Can\'t check existance of magic properties');

        $document = $this->getHTMLDocument();
        $th = $document->createElement('th');
        self::assertTrue(method_exists($th, 'cellIndex'));
        $td = $document->createElement('td');
        self::assertTrue(method_exists($td, 'cellIndex'));
    }

    public function testForCellsWithoutAParentShouldReturnNegativeOne(): void
    {
        $document = $this->getHTMLDocument();
        $th = $document->createElement('th');
        self::assertSame(-1, $th->cellIndex);
        $td = $document->createElement('td');
        self::assertSame(-1, $td->cellIndex);
    }

    public function testForCellsWhoseParentIsNotATrShouldReturnNegativeOne(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $th = $table->appendChild($document->createElement('th'));
        self::assertSame(-1, $th->cellIndex);
        $td = $table->appendChild($document->createElement('td'));
        self::assertSame(-1, $td->cellIndex);
    }

    public function testForCellsWhoseParentIsNotAHTMLTrShouldReturnNegativeOne(): void
    {
        $document = $this->getHTMLDocument();
        $tr = $document->createElementNS('', 'tr');
        $th = $tr->appendChild($document->createElement('th'));
        self::assertSame(-1, $th->cellIndex);
        $td = $tr->appendChild($document->createElement('td'));
        self::assertSame(-1, $td->cellIndex);
    }

    public function testForCellsWhoseParentIsATrShouldReturnIndex(): void
    {
        $document = $this->getHTMLDocument();
        $tr = $document->createElement('tr');
        $th = $tr->appendChild($document->createElement('th'));
        self::assertSame(0, $th->cellIndex);
        $td = $tr->appendChild($document->createElement('td'));
        self::assertSame(1, $td->cellIndex);
    }

    public function testForCellsWhoseParentIsATrWithNonTdThSiblingShouldSkipThoseSiblings(): void
    {
        $document = $this->getHTMLDocument();
        $tr = $document->createElement('tr');
        $th = $tr->appendChild($document->createElement('th'));
        self::assertSame(0, $th->cellIndex);
        $tr->appendChild($document->createElement('div'));
        $tr->appendChild($document->createTextNode('Hello World'));
        $td = $tr->appendChild($document->createElement('td'));
        self::assertSame(1, $td->cellIndex);
    }
}
