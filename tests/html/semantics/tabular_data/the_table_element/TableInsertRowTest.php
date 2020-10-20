<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\DocumentGetter;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/table-insertRow.html
 */
class TableInsertRowTest extends TableTestCase
{
    use DocumentGetter;

    public function testInsertRowShouldNotCopyPrefixes(): void
    {
        $parentEl = $this->getHTMLDocument()->createElementNS(Namespaces::HTML, 'html:table');
        self::assertSame(Namespaces::HTML, $parentEl->namespaceURI);
        self::assertSame('html', $parentEl->prefix);
        self::assertSame('table', $parentEl->localName);
        self::assertSame('HTML:TABLE', $parentEl->tagName);

        $row = $parentEl->insertRow(-1);
        self::assertSame(Namespaces::HTML, $row->namespaceURI);
        self::assertNull($row->prefix);
        self::assertSame('tr', $row->localName);
        self::assertSame('TR', $row->tagName);

        $body = $row->parentNode;
        self::assertSame(Namespaces::HTML, $body->namespaceURI);
        self::assertNull($body->prefix);
        self::assertSame('tbody', $body->localName);
        self::assertSame('TBODY', $body->tagName);

        self::assertSame([$body], iterator_to_array($parentEl->childNodes));
        self::assertSame([$row], iterator_to_array($body->childNodes));
        self::assertSame([$row], iterator_to_array($parentEl->rows));
    }

    public function testInsertRowShouldInsertIntoATbodyNotIntoATheadIfTableRowsIsEmpty(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $head = $table->appendChild($document->createElement('thead'));
        self::assertSame([], iterator_to_array($table->rows));

        $row = $table->insertRow(-1);
        $body = $row->parentNode;
        self::assertSame([$head, $body], iterator_to_array($table->childNodes));
        self::assertSame([], iterator_to_array($head->childNodes));
        self::assertSame([$row], iterator_to_array($body->childNodes));
        self::assertSame([$row], iterator_to_array($table->rows));
    }

    public function testInsertRowIntoATbodyNotIntoATfootIfTableRowsIsEmpty(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $tfoot = $table->appendChild($document->createElement('tfoot'));
        self::assertSame([], iterator_to_array($table->rows));

        $row = $table->insertRow(-1);
        $body = $row->parentNode;
        self::assertSame([$tfoot, $body], iterator_to_array($table->childNodes));
        self::assertSame([], iterator_to_array($tfoot->childNodes));
        self::assertSame([$row], iterator_to_array($body->childNodes));
        self::assertSame([$row], iterator_to_array($table->rows));
    }
}
