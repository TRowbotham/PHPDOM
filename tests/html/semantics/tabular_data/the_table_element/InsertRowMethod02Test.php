<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/insertRow-method-02.html
 */
class InsertRowMethod02Test extends TableTestCase
{
    use WindowTrait;

    public function testTableShouldStartOutEmpty(): HTMLTableElement
    {
        $table = self::getWindow()->document->getElementById('test')->getElementsByTagName('table')[0];

        self::assertSame(0, $table->childNodes->length);
        self::assertSame(0, $table->rows->length);

        return $table;
    }

    /**
     * @depends testTableShouldStartOutEmpty
     */
    public function testInsertRowShouldInsertATrElement(HTMLTableElement $table): array
    {
        $tr = $table->insertRow(0);
        self::assertSame('tr', $tr->localName);
        self::assertSame(Namespaces::HTML, $tr->namespaceURI);

        return [$table, $tr];
    }

    /**
     * @depends testInsertRowShouldInsertATrElement
     */
    public function testInsertRowShouldInsertATbodyElement(array $elements): void
    {
        [$table, $tr] = $elements;
        $tbody = $tr->parentNode;
        self::assertSame('tbody', $tbody->localName);
        self::assertSame(Namespaces::HTML, $tbody->namespaceURI);
        self::assertSame($table, $tbody->parentNode);
    }

    public static function getDocumentName(): string
    {
        return 'insertRow-method-02.html';
    }
}
