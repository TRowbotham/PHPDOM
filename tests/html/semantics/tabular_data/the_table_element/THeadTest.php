<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/tHead.html
 */
class THeadTest extends TableTestCase
{
    use WindowTrait;

    public function test1(): void
    {
        $document = self::getWindow()->document;
        $t = $document->getElementById('t');
        $thead1 = $document->getElementById('thead1');

        self::assertSame($thead1, $t->tHead);

        $thead2 = $document->getElementById('thead2');
        $t->tHead = null;

        self::assertSame($thead2, $t->tHead);

        $thead3 = $document->getElementById('thead3');
        $t->deleteTHead();

        self::assertSame($thead3, $t->tHead);

        $thead = $t->createTHead();
        self::assertSame($thead, $t->tHead);
        self::assertSame($thead3, $thead);

        $t->deleteTHead();
        self::assertNull($t->tHead);

        $tcaption = $document->getElementById('tcaption');
        $tbody1 = $document->getElementById('tbody1');

        $thead = $t->createTHead();
        self::assertSame($thead, $t->tHead);

        self::assertSame($tcaption, $t->tHead->previousSibling);
        self::assertSame($tbody1, $t->tHead->nextSibling);

        $this->assertThrows(static function () use ($document, $t): void {
            $t->tHead = $document->createElement('div');
        }, TypeError::class);

        $this->assertThrows(static function () use ($document, $t): void {
            $t->tHead = $document->createElement('tbody');
        }, HierarchyRequestError::class);
    }

    // public function test2(): void
    // {
    //     $document = self::getWindow()->document;
    //     $t2 = $document->getElementById('t2');
    //     $t2thead = $document->getElementById('t2thead');
    //     $this->expectException(HierarchyRequestError::class);
    //     $t2->tHead = $t2thead;
    // }

    // public function test3(): void
    // {
    //     $document = self::getWindow()->document;
    //     $table = $document->createElementNS("http://www.w3.org/1999/xhtml", "foo:table");
    //     $thead = $table->createTHead();

    //     self::assertSame($thead, $table->tHead);
    //     self::assertNull($thead->prefix);
    // }

    public static function getDocumentName(): string
    {
        return 'tHead.html';
    }
}
