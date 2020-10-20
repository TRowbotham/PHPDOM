<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/tFoot.html
 */
class TFootTest extends TableTestCase
{
    use WindowTrait;

    public function test1(): void
    {
        $document = self::getWindow()->document;
        $t = $document->getElementById('t');
        $tfoot1 = $document->getElementById('tfoot1');

        self::assertSame($tfoot1, $t->tFoot);

        $tfoot2 = $document->getElementById('tfoot2');
        $t->tFoot = null;

        self::assertSame($tfoot2, $t->tFoot);

        $tfoot3 = $document->getElementById('tfoot3');
        $t->deleteTFoot();

        self::assertSame($tfoot3, $t->tFoot);

        $tfoot = $t->createTFoot();
        self::assertSame($tfoot, $t->tFoot);
        self::assertSame($tfoot3, $tfoot);

        $t->deleteTFoot();
        self::assertNull($t->tFoot);

        $tbody2 = $document->getElementById('tbody2');

        $tfoot = $t->createTFoot();
        self::assertSame($tfoot, $t->tFoot);

        self::assertSame($tbody2, $t->tFoot->previousSibling);
        self::assertNull($t->tFoot->nextSibling);

        $t->deleteTFoot();
        self::assertNull($t->tFoot);

        $t->tFoot = $tfoot;
        self::assertSame($tfoot, $t->tFoot);

        self::assertSame($tbody2, $t->tFoot->previousSibling);
        self::assertNull($t->tFoot->nextSibling);

        $this->assertThrows(static function () use ($document, $t): void {
            $t->tFoot = $document->createElement('div');
        }, TypeError::class);

        $this->assertThrows(static function () use ($document, $t): void {
            $t->tFoot = $document->createElement('thead');
        }, HierarchyRequestError::class);
    }

    public function test2(): void
    {
        $document = self::getWindow()->document;
        $table = $document->createElementNS("http://www.w3.org/1999/xhtml", "foo:table");
        $tfoot = $table->createTFoot();

        self::assertSame($tfoot, $table->tFoot);
        self::assertNull($tfoot->prefix);
    }

    public static function getDocumentName(): string
    {
        return 'tFoot.html';
    }
}
