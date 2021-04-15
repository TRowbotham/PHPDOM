<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tbody_element;

use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tbody-element/insertRow.html
 */
class InsertRowTest extends TestCase
{
    use WindowTrait;

    public function testInsertRowWithZeroArg(): HTMLTableSectionElement
    {
        $tbody = self::getWindow()->document->getElementById('testBody');
        $trEle = $tbody->insertRow(0);
        self::assertSame($trEle, $tbody->rows[0]);
        self::assertSame(2, $tbody->rows->length);

        return $tbody;
    }

    /**
     * @depends testInsertRowWithZeroArg
     */
    public function testInsertRowWithNegativeOneArg(HTMLTableSectionElement $tbody): HTMLTableSectionElement
    {
        $trEle = $tbody->insertRow(-1);
        self::assertSame($trEle, $tbody->rows[$tbody->rows->length - 1]);
        self::assertSame(3, $tbody->rows->length);

        return $tbody;
    }

    /**
     * @depends testInsertRowWithNegativeOneArg
     */
    public function testInsertRowWithNoArg(HTMLTableSectionElement $tbody): HTMLTableSectionElement
    {
        $trEle = $tbody->insertRow();
        self::assertSame($trEle, $tbody->rows[$tbody->rows->length - 1]);
        self::assertSame(4, $tbody->rows->length);

        return $tbody;
    }

    /**
     * @depends testInsertRowWithNoArg
     */
    public function testInsertRowWithSameNumberOfRowsArg(HTMLTableSectionElement $tbody): void
    {
        $trEle = $tbody->insertRow($tbody->rows->length);
        self::assertSame($trEle, $tbody->rows[$tbody->rows->length - 1]);
        self::assertSame(5, $tbody->rows->length);
    }

    public function testInsertRowWithTooSmallIndexThrows(): void
    {
        $tbody = self::getWindow()->document->getElementById('testBody');
        $this->expectException(IndexSizeError::class);
        $tbody->insertRow(-2);
    }

    public function testInsertRowWithTooLargeIndexThrows(): void
    {
        $tbody = self::getWindow()->document->getElementById('testBody');
        $this->expectException(IndexSizeError::class);
        $tbody->insertRow($tbody->rows->length + 1);
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'insertRow.html';
    }
}
