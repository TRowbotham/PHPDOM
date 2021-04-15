<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tr_element;

use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tr-element/sectionRowIndex.html
 */
class SectionRowIndexTest extends TestCase
{
    use WindowTrait;

    public function testRowInTheadHtml(): void
    {
        $tHeadRow = self::getWindow()->document->getElementById('ht1');
        self::assertSame(0, $tHeadRow->sectionRowIndex);
    }

    public function testRowInImplicitTbodyHtml(): void
    {
        $tRow1 = self::getWindow()->document->getElementById('t1');
        self::assertSame(0, $tRow1->sectionRowIndex);
    }

    public function testOtherRowInImplicitTbodyHtml(): void
    {
        $tRow2 = self::getWindow()->document->getElementById('t2');
        self::assertSame(1, $tRow2->sectionRowIndex);
    }

    public function testRowInExplicitTbodyHtml(): void
    {
        $tBodyRow = self::getWindow()->document->getElementById('bt1');
        self::assertSame(1, $tBodyRow->sectionRowIndex);
    }

    public function testRowInImplicitTfootHtml(): void
    {
        $tFootRow = self::getWindow()->document->getElementById('ft1');
        self::assertSame(2, $tFootRow->sectionRowIndex);
    }

    public function testRowInTheadInNestedTableHtml(): void
    {
        $childHeadRow = self::getWindow()->document->getElementById('nht1');
        self::assertSame(0, $childHeadRow->sectionRowIndex);
    }

    public function testRowInImplicitTbodyInNestedTableHtml(): void
    {
        $childRow = self::getWindow()->document->getElementById('nt1');
        self::assertSame(1, $childRow->sectionRowIndex);
    }

    public function testRowInExplicitTbodyInNestedTableHtml(): void
    {
        $childRow = self::getWindow()->document->getElementById('nbt1');
        self::assertSame(0, $childRow->sectionRowIndex);
    }

    public function testRowInScriptCreatedTable(): void
    {
        self::assertSame(0, $this->mkTrElm([])->sectionRowIndex);
    }

    public function testRowInScriptCreatedDivInTable(): void
    {
        self::assertSame(-1, $this->mkTrElm(['div'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTheadInTable(): void
    {
        self::assertSame(0, $this->mkTrElm(['thead'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTbodyInTable(): void
    {
        self::assertSame(0, $this->mkTrElm(['tbody'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTfootInTable(): void
    {
        self::assertSame(0, $this->mkTrElm(['tfoot'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTrInTbodyInTable(): void
    {
        self::assertSame(-1, $this->mkTrElm(['tbody', 'tr'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTdInTrInTbodyInTable(): void
    {
        self::assertSame(-1, $this->mkTrElm(['tbody', 'tr', 'td'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedNestedTable(): void
    {
        self::assertSame(0, $this->mkTrElm(['tbody', 'tr', 'td', 'table'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTheadInNestedTable(): void
    {
        self::assertSame(0, $this->mkTrElm(['tbody', 'tr', 'td', 'table', 'thead'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTbodyInNestedTable(): void
    {
        self::assertSame(0, $this->mkTrElm(['tbody', 'tr', 'td', 'table', 'tbody'])->sectionRowIndex);
    }

    public function testRowInScriptCreatedTfootInNestedTable(): void
    {
        self::assertSame(0, $this->mkTrElm(['tbody', 'tr', 'td', 'table', 'tfoot'])->sectionRowIndex);
    }

    public function mkTrElm(array $elst): HTMLTableRowElement
    {
        $document = self::getWindow()->document;
        $elm = $document->createElement('table');

        foreach ($elst as $item) {
            $elm = $elm->appendChild($document->createElement($item));
        }

        return $elm->appendChild($document->createElement('tr'));
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'sectionRowIndex.html';
    }
}
