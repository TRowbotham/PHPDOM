<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/insertRow-method-01.html
 */
class InsertRowMethod01Test extends TableTestCase
{
    use WindowTrait;

    public function test(): void
    {
        $table = self::getWindow()->document->getElementsByTagName('table')[0];

        $this->assertThrows(static function () use ($table): void {
            $table->insertRow(-2);
        }, IndexSizeError::class);
        $this->assertThrows(static function () use ($table): void {
            $table->insertRow(2);
        }, IndexSizeError::class);
    }

    public static function getDocumentName(): string
    {
        return 'insertRow-method-01.html';
    }
}
