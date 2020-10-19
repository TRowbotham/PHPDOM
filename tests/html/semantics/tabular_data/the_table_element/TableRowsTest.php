<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Generator;
use Rowbot\DOM\Element\HTML\HTMLTableElement;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

use function count;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/table-rows.html
 */
class TableRowsTest extends TestCase
{
    use DocumentGetter;

    /**
     * @dataProvider tableGroupsProvider
     */
    public function testTableSimple($group, HTMLTableElement $table): void
    {
        $document = $this->getHTMLDocument();

        $foo1 = $group->appendChild($document->createElement('tr'));
        $foo1->id = 'foo';
        $bar1 = $group->appendChild($document->createElement('tr'));
        $bar1->id = 'bar';
        $foo2 = $group->appendChild($document->createElement('tr'));
        $foo2->id = 'foo';
        $bar2 = $group->appendChild($document->createElement('tr'));
        $bar2->id = 'bar';

        self::assertInstanceOf(HTMLCollection::class, $table->rows);
        self::assertNodeListEquals([$foo1, $bar1, $foo2, $bar2], $table->rows);
        self::assertSame($foo1, $table->rows['foo']);
        self::assertSame($foo1, $table->rows->namedItem('foo'));
        self::assertSame($bar1, $table->rows['bar']);
        self::assertSame($bar1, $table->rows->namedItem('bar'));
    }

    public function testTableRows(): void
    {
        $document = $this->getHTMLDocument();

        $table = $document->createElement("table");
        $orphan1 = $table->appendChild($document->createElement("tr"));
        $orphan1->id = "orphan1";
        $foot1 = $table->appendChild($document->createElement("tfoot"));
        $orphan2 = $table->appendChild($document->createElement("tr"));
        $orphan2->id = "orphan2";
        $foot2 = $table->appendChild($document->createElement("tfoot"));
        $orphan3 = $table->appendChild($document->createElement("tr"));
        $orphan3->id = "orphan3";
        $body1 = $table->appendChild($document->createElement("tbody"));
        $orphan4 = $table->appendChild($document->createElement("tr"));
        $orphan4->id = "orphan4";
        $body2 = $table->appendChild($document->createElement("tbody"));
        $orphan5 = $table->appendChild($document->createElement("tr"));
        $orphan5->id = "orphan5";
        $head1 = $table->appendChild($document->createElement("thead"));
        $orphan6 = $table->appendChild($document->createElement("tr"));
        $orphan6->id = "orphan6";
        $head2 = $table->appendChild($document->createElement("thead"));
        $orphan7 = $table->appendChild($document->createElement("tr"));
        $orphan7->id = "orphan7";

        $foot1row1 = $foot1->appendChild($document->createElement("tr"));
        $foot1row1->id = "foot1row1";
        $foot1row2 = $foot1->appendChild($document->createElement("tr"));
        $foot1row2->id = "foot1row2";
        $foot2row1 = $foot2->appendChild($document->createElement("tr"));
        $foot2row1->id = "foot2row1";
        $foot2row2 = $foot2->appendChild($document->createElement("tr"));
        $foot2row2->id = "foot2row2";

        $body1row1 = $body1->appendChild($document->createElement("tr"));
        $body1row1->id = "body1row1";
        $body1row2 = $body1->appendChild($document->createElement("tr"));
        $body1row2->id = "body1row2";
        $body2row1 = $body2->appendChild($document->createElement("tr"));
        $body2row1->id = "body2row1";
        $body2row2 = $body2->appendChild($document->createElement("tr"));
        $body2row2->id = "body2row2";

        $head1row1 = $head1->appendChild($document->createElement("tr"));
        $head1row1->id = "head1row1";
        $head1row2 = $head1->appendChild($document->createElement("tr"));
        $head1row2->id = "head1row2";
        $head2row1 = $head2->appendChild($document->createElement("tr"));
        $head2row1->id = "head2row1";
        $head2row2 = $head2->appendChild($document->createElement("tr"));
        $head2row2->id = "head2row2";

        // These elements should not end up in any collection.
        $table->appendChild($document->createElement("div"))
            ->appendChild($document->createElement("tr"));
        $foot1->appendChild($document->createElement("div"))
            ->appendChild($document->createElement("tr"));
        $body1->appendChild($document->createElement("div"))
            ->appendChild($document->createElement("tr"));
        $head1->appendChild($document->createElement("div"))
            ->appendChild($document->createElement("tr"));
        $table->appendChild($document->createElementNS("http://example.com/test", "tr"));
        $foot1->appendChild($document->createElementNS("http://example.com/test", "tr"));
        $body1->appendChild($document->createElementNS("http://example.com/test", "tr"));
        $head1->appendChild($document->createElementNS("http://example.com/test", "tr"));

        self::assertInstanceOf(HTMLCollection::class, $table->rows);
        self::assertNodeListEquals([
            // thead
            $head1row1,
            $head1row2,
            $head2row1,
            $head2row2,

            // tbody + table
            $orphan1,
            $orphan2,
            $orphan3,
            $body1row1,
            $body1row2,
            $orphan4,
            $body2row1,
            $body2row2,
            $orphan5,
            $orphan6,
            $orphan7,

            // tfoot
            $foot1row1,
            $foot1row2,
            $foot2row1,
            $foot2row2,
        ], $table->rows);

        // assert_array_equals(Object.getOwnPropertyNames(table.rows), [
        //     "0",
        //     "1",
        //     "2",
        //     "3",
        //     "4",
        //     "5",
        //     "6",
        //     "7",
        //     "8",
        //     "9",
        //     "10",
        //     "11",
        //     "12",
        //     "13",
        //     "14",
        //     "15",
        //     "16",
        //     "17",
        //     "18",
        //     "head1row1",
        //     "head1row2",
        //     "head2row1",
        //     "head2row2",
        //     "orphan1",
        //     "orphan2",
        //     "orphan3",
        //     "body1row1",
        //     "body1row2",
        //     "orphan4",
        //     "body2row1",
        //     "body2row2",
        //     "orphan5",
        //     "orphan6",
        //     "orphan7",
        //     "foot1row1",
        //     "foot1row2",
        //     "foot2row1",
        //     "foot2row2"
        //   ]);

        $ids = [
            "orphan1",
            "orphan2",
            "orphan3",
            "orphan4",
            "orphan5",
            "orphan6",
            "orphan7",
            "foot1row1",
            "foot1row2",
            "foot2row1",
            "foot2row2",
            "body1row1",
            "body1row2",
            "body2row1",
            "body2row2",
            "head1row1",
            "head1row2",
            "head2row1",
            "head2row2",
        ];

        foreach ($ids as $id) {
            self::assertSame($id, $table->rows->namedItem($id)->id);
            self::assertTrue(isset($table->rows[$id]));
            self::assertSame($id, $table->rows[$id]->id);
            self::assertTrue(isset($table->rows[$id]));
        }

        while ($table->firstChild) {
            $table->removeChild($table->firstChild);
        }

        foreach ($ids as $id) {
            self::assertNull($table->rows->namedItem($id));
            self::assertFalse(isset($table->rows[$id]));
            self::assertNull($table->rows[$id]);
            self::assertFalse(isset($table->rows[$id]));
        }
    }

    public function tableGroupsProvider(): Generator
    {
        $document = $this->getHTMLDocument();

        $table = $document->createElement('table');

        yield [$table, $table];

        $table = $document->createElement('table');
        $group = $table->appendChild($document->createElement('thead'));

        yield [$group, $table];

        $table = $document->createElement('table');
        $group = $table->appendChild($document->createElement('tbody'));

        yield [$group, $table];

        $table = $document->createElement('table');
        $group = $table->appendChild($document->createElement('tfoot'));

        yield [$group, $table];
    }

    private static function assertNodeListEquals(array $expected, $actual): void
    {
        self::assertSame(count($expected), count($actual));

        $length = count($actual);

        for ($i = 0; $i < $length; ++$i) {
            self::assertTrue(isset($actual[$i]));
            self::assertSame($expected[$i], $actual->item($i));
            self::assertSame($expected[$i], $actual[$i]);
        }
    }
}
