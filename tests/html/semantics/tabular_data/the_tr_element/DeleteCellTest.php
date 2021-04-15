<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tr_element;

use Rowbot\DOM\Element\HTML\HTMLTableRowElement;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tr-element/deleteCell.html
 */
class DeleteCellTest extends TestCase
{
    use WindowTrait;

    public function testDeleteCellWithArgZero(): HTMLTableRowElement
    {
        $tr = self::getWindow()->document->getElementById('testTr');
        $tr->deleteCell(0);
        self::assertSame('12345', $tr->cells[0]->innerHTML);
        self::assertSame(2, $tr->cells->length);

        return $tr;
    }

    /**
     * @depends testDeleteCellWithArgZero
     */
    public function testDeleteCellWithArgNegativeOne(HTMLTableRowElement $tr): HTMLTableRowElement
    {
        $tr->deleteCell(-1);
        self::assertSame('12345', $tr->cells[$tr->cells->length - 1]->innerHTML);
        self::assertSame(1, $tr->cells->length);

        return $tr;
    }

    public function testDeleteCellWithArgLessThanNegativeOne(): void
    {
        $tr = self::getWindow()->document->getElementById('testTr');
        $this->expectException(IndexSizeError::class);
        $tr->deleteCell($tr->cells->length);
    }

    /**
     * @depends testDeleteCellWithArgNegativeOne
     */
    public function testDeleteCellWithArgNegativeOneWithNoCells(HTMLTableRowElement $tr): void
    {
        self::assertSame(1, $tr->cells->length);
        $tr->deleteCell(-1);
        self::assertSame(0, $tr->cells->length);
        $tr->deleteCell(-1);
        self::assertSame(0, $tr->cells->length);
    }

    public function testDeleteCellWithArgZeroWithNoCells(): void
    {
        $tr = self::getWindow()->document->getElementById('testTr');
        self::assertSame(0, $tr->cells->length);
        $this->expectException(IndexSizeError::class);
        $tr->deleteCell(0);
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'deleteCell.html';
    }
}
