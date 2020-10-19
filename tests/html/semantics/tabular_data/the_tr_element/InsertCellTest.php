<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tr_element;

use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tr-element/insertCell.html
 */
class InsertCellTest extends TestCase
{
    use WindowTrait;

    private static $tr;

    public function testInsertCellWithArgZero(): HTMLTableRowElement
    {
        $tdEle = self::$tr->insertCell(0);
        self::assertSame($tdEle, self::$tr->cells[0]);
        self::assertSame(1, self::$tr->cells->length);

        return self::$tr;
    }

    /**
     * @depends testInsertCellWithArgZero
     */
    public function testInsertCellWithArgNegativeOne(HTMLTableRowElement $tr): HTMLTableRowElement
    {
        $tdEle = $tr->insertCell(-1);
        self::assertSame($tdEle, $tr->cells[$tr->cells->length - 1]);
        self::assertSame(2, $tr->cells->length);

        return $tr;
    }

    /**
     * @depends testInsertCellWithArgNegativeOne
     */
    public function testInsertCellWithArgCellsLength(HTMLTableRowElement $tr): HTMLTableRowElement
    {
        $tdEle = $tr->insertCell($tr->cells->length);
        self::assertSame($tdEle, $tr->cells[$tr->cells->length - 1]);
        self::assertSame(3, $tr->cells->length);

        return $tr;
    }

    /**
     * @depends testInsertCellWithArgCellsLength
     */
    public function testInsertCellWithNoArgs(HTMLTableRowElement $tr): void
    {
        $tdEle = $tr->insertCell();
        self::assertSame($tdEle, $tr->cells[$tr->cells->length - 1]);
        self::assertSame(4, $tr->cells->length);
    }

    public function testInsertCellLessThanNegativeOne(): void
    {
        $this->expectException(IndexSizeError::class);
        self::$tr->insertCell(-2);
    }

    public function testInsertCellGreaterThanCellCount(): void
    {
        $this->expectException(IndexSizeError::class);
        self::$tr->insertCell(self::$tr->cells->length + 1);
    }

    public function testInsertCellWillNotCopyTablesPrefix(): void
    {
        $table = self::getWindow()->document->createElementNS("http://www.w3.org/1999/xhtml", "foo:table");
        $row = $table->insertRow(0);
        $cell = $row->insertCell(0);

        self::assertSame($cell, $row->cells[0]);
        self::assertNull($cell->prefix);
    }

    public static function setUpBeforeClass(): void
    {
        self::$tr = self::getWindow()->document->getElementById('testTr');
        self::registerCleanup(static function (): void {
            self::$tr = null;
        });
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'insertCell.html';
    }
}
