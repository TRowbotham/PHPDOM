<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data;

use Rowbot\DOM\Tests\dom\DocumentGetter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/html-table-section-element.js
 */
trait HtmlTableSectionElementTrait
{
    use DocumentGetter;

    abstract public function getTableSectionName(): string;

    public function testRowsAttribute(): void
    {
        $localName = $this->getTableSectionName();
        $document = $this->getHTMLDocument();
        $elem = $document->createElement($localName);
        self::assertSame(0, $elem->rows->length);

        // Child <p> should *not* count as a row
        $elem->appendChild($document->createElement("p"));
        self::assertSame(0, $elem->rows->length);

        // Child <tr> should count as a row
        $childTr = $document->createElement("tr");
        $elem->appendChild($childTr);
        self::assertSame(1, $elem->rows->length);

        // Nested table with child <tr> should *not* count as a row
        $nested = $document->createElement($localName);
        $nested->appendChild($document->createElement("tr"));
        $nestedTable = $document->createElement("table");
        $nestedTable->appendChild($nested);
        $childTr->appendChild($nestedTable);
        self::assertSame(1, $elem->rows->length);
    }
}
