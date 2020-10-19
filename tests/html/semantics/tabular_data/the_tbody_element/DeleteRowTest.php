<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_tbody_element;

use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-tbody-element/deleteRow.html
 */
class DeleteRowTest extends TestCase
{
    use WindowTrait;

    public function testDeleteRowWithZeroArg(): HTMLTableSectionElement
    {
        $tbody = self::getWindow()->document->getElementById('testBody');
        $tbody->deleteRow(0);
        self::assertSame(2, $tbody->rows->length);
        self::assertSame('12345', $tbody->rows[0]->childNodes[0]->innerHTML);

        return $tbody;
    }

    /**
     * @depends testDeleteRowWithZeroArg
     */
    public function testDeleteRowWithNegativeOneArg(HTMLTableSectionElement $tbody): HTMLTableSectionElement
    {
        $tbody->deleteRow(-1);
        self::assertSame(1, $tbody->rows->length);
        self::assertSame('12345', $tbody->rows[0]->childNodes[0]->innerHTML);

        return $tbody;
    }

    public function testDeleteRowWithArgEqualToRowLengthThrows(): void
    {
        $tbody = self::getWindow()->document->getElementById('testBody');
        $this->expectException(IndexSizeError::class);
        $tbody->deleteRow($tbody->rows->length);
    }

    public function testDeleteRowArgLessThanNegativeOne(): void
    {
        $tbody = self::getWindow()->document->getElementById('testBody');
        $this->expectException(IndexSizeError::class);
        $tbody->deleteRow(-2);
    }

    /**
     * @depends testDeleteRowWithNegativeOneArg
     */
    public function testDeleteRowWithNegativeOneArgNoRows(HTMLTableSectionElement $tbody): HTMLTableSectionElement
    {
        self::assertSame(1, $tbody->rows->length);
        $tbody->deleteRow(-1);
        self::assertSame(0, $tbody->rows->length);
        $tbody->deleteRow(-1);
        self::assertSame(0, $tbody->rows->length);

        return $tbody;
    }

    /**
     * @depends testDeleteRowWithNegativeOneArgNoRows
     */
    public function testDeleteRowWithZeroArgNoRows(HTMLTableSectionElement $tbody): void
    {
        self::assertSame(0, $tbody->rows->length);
        $this->expectException(IndexSizeError::class);
        $tbody->deleteRow(0);
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'deleteRow.html';
    }
}
