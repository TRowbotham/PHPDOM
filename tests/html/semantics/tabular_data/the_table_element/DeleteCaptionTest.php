<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/delete-caption.html
 */
class DeleteCaptionTest extends TableTestCase
{
    use WindowTrait;

    // The deleteCaption() method must remove the first caption element child of the table element, if any.
    // https://html.spec.whatwgorg/multipage/tables.html#dom-table-deletecaption
    public function testDeleteCaptionDeleteOnlyCaptionOnTable(): void
    {
        $table = self::getWindow()->document->getElementById('one-caption');
        $table->deleteCaption();
        self::assertSame(0, $table->getElementsByTagName('caption')->length);
    }

    public function testDeleteCaptionReturnsUndefined(): void
    {
        $table = self::getWindow()->document->getElementById('one-caption');
        $result = $table->deleteCaption();
        // does .deleteCaption() have a return value?
        self::assertNull($result);
    }

    public function testDeleteCaption(): void
    {
        $table = self::getWindow()->document->getElementById('two-captions');

        $table->deleteCaption();
        self::assertSame(1, $table->getElementsByTagName('caption')->length);
        self::assertSame('A second caption element', $table->getElementsByTagName('caption')[0]->textContent);

        // removing the only caption
        $table->deleteCaption();
        self::assertSame(0, $table->getElementsByTagName('caption')->length);
    }

    public function testDeleteCaptionDoesNotThrowAnyExceptionsWhenCalledOnATableWithoutACaption(): void
    {
        $table = self::getWindow()->document->getElementById('zero-captions');

        // removing the only caption
        $table->deleteCaption();
        self::assertSame(0, $table->getElementsByTagName('caption')->length);
    }

    public function testDeleteCaptionDoesNotDeleteCaptionsInDescendantTables(): void
    {
        $table = self::getWindow()->document->getElementById('descendent-caption');
        $table->deleteCaption();

        self::assertSame(1, $table->getElementsByTagName('caption')->length);
    }

    public function testDeleteCaptionHandlesCaptionsFromDifferentNamespaces(): void
    {
        $table = self::getWindow()->document->getElementById('zero-captions');

        $caption = self::getWindow()->document->createElementNS('http://www.w3.org/2000/svg', 'caption');
        $table->insertBefore($caption, $table->firstChild);
        self::assertSame(1, $table->getElementsByTagName('caption')->length);

        $table->deleteCaption();
        self::assertSame(1, $table->getElementsByTagName('caption')->length);
    }

    public static function getDocumentName(): string
    {
        return 'delete-caption.html';
    }
}
