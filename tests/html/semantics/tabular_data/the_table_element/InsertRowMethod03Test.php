<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/insertRow-method-03.html
 */
class InsertRowMethod03Test extends TableTestCase
{
    use WindowTrait;

    public function testTableShouldStartOutWithTwoRows(): HTMLTableElement
    {
        $table = self::getWindow()->document->getElementById('test')->getElementsByTagName('table')[0];
        self::assertSame(3, $table->childNodes->length);
        self::assertSame(2, $table->rows->length);

        return $table;
    }

    /**
     * @depends testTableShouldStartOutWithTwoRows
     */
    public function testInsertRowShouldInsertATrElementBeforeTheSecondRow(HTMLTableElement $table): void
    {
        $tr = $table->insertRow(1);
        self::assertSame('tr', $tr->localName);
        self::assertSame(Namespaces::HTML, $tr->namespaceURI);
        self::assertSame('first', $table->getElementsByTagName('tr')[0]->id);
        self::assertSame('', $table->getElementsByTagName('tr')[1]->id);
        self::assertSame('second', $table->getElementsByTagName('tr')[2]->id);
    }

    public static function getDocumentName(): string
    {
        return 'insertRow-method-03.html';
    }
}
