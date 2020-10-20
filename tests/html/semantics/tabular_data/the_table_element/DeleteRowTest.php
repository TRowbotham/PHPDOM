<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/remove-row.html
 */
class DeleteRowTest extends TableTestCase
{
    use WindowTrait;

    public function testDeleteRowFunctionInvalidArg(): HTMLTableElement
    {
        $el = self::getWindow()->document->getElementById('element');
        $this->assertThrows(static function () use ($el): void {
            $el->deleteRow(-2);
        }, IndexSizeError::class);

        return $el;
    }

    /**
     * @depends testDeleteRowFunctionInvalidArg
     */
    public function testDeleteRowFunctionInvalidArgBis(HTMLTableElement $el): HTMLTableElement
    {
        $this->assertThrows(static function () use ($el): void {
            $el->deleteRow($el->rows->length);
        }, IndexSizeError::class);

        return $el;
    }

    /**
     * @depends testDeleteRowFunctionInvalidArgBis
     */
    public function testCheckNormalDeleteRow(HTMLTableElement $el): HTMLTableElement
    {
        $oldLength = $el->rows->length;
        $el->insertRow(-1);
        $el->deleteRow(-1);
        self::assertSame($oldLength, $el->rows->length);

        return $el;
    }

    /**
     * @depends testCheckNormalDeleteRow
     */
    public function testCheckNormalDeleteRowBis(HTMLTableElement $el): HTMLTableElement
    {
        self::assertSame(3, $el->rows->length);

        do {
            $oldLength = $el->rows->length;
            $el->deleteRow(-1);
            self::assertSame($oldLength - 1, $el->rows->length);
        } while ($el->rows->length);

        return $el;
    }

    /**
     * @depends testCheckNormalDeleteRowBis
     */
    public function testDeleteRowWithArgNegativeOneWithNoRows(HTMLTableElement $el): HTMLTableElement
    {
        self::assertSame(0, $el->rows->length);
        $el->deleteRow(-1);

        return $el;
    }

    /**
     * @depends testDeleteRowWithArgNegativeOneWithNoRows
     */
    public function testDeleteRowWithArgZeroWithNoRows(HTMLTableElement $el): void
    {
        self::assertSame(0, $el->rows->length);
        $this->expectException(IndexSizeError::class);
        $el->deleteRow(0);
    }

    public static function getDocumentName(): string
    {
        return 'remove-row.html';
    }
}
