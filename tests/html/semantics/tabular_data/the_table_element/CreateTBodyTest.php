<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Element\HTML\HTMLTableSectionElement;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\DocumentGetter;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/createTBody.html
 */
class CreateTBodyTest extends TableTestCase
{
    use DocumentGetter;

    public static function assertTBody(HTMLTableSectionElement $tbody): void
    {
        self::assertSame('tbody', $tbody->localName);
        self::assertSame(Namespaces::HTML, $tbody->namespaceURI);
        self::assertNull($tbody->prefix);
    }

    public function testNoChildNodes(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $tbody = $table->createTBody();
        self::assertSame($tbody, $table->firstChild);
        self::assertTBody($tbody);
    }

    public function testOneTbodyChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before = $table->appendChild($document->createElement("tbody"));
        self::assertSame([$before], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before, $tbody], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testTwoTbodyChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before1 = $table->appendChild($document->createElement("tbody"));
        $before2 = $table->appendChild($document->createElement("tbody"));
        self::assertSame([$before1, $before2], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before1, $before2, $tbody], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATheadAndATbodyChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before1 = $table->appendChild($document->createElement("thead"));
        $before2 = $table->appendChild($document->createElement("tbody"));
        self::assertSame([$before1, $before2], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before1, $before2, $tbody], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATfootAndATbodyChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before1 = $table->appendChild($document->createElement("tfoot"));
        $before2 = $table->appendChild($document->createElement("tbody"));
        self::assertSame([$before1, $before2], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before1, $before2, $tbody], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATbodyAndATheadChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before = $table->appendChild($document->createElement("tbody"));
        $after = $table->appendChild($document->createElement("thead"));
        self::assertSame([$before, $after], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before, $tbody, $after], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATbodyAndATfootChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before = $table->appendChild($document->createElement("tbody"));
        $after = $table->appendChild($document->createElement("tfoot"));
        self::assertSame([$before, $after], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before, $tbody, $after], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testTwoTbodyChildNodesAndADiv(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before1 = $table->appendChild($document->createElement("tbody"));
        $before2 = $table->appendChild($document->createElement("tbody"));
        $after = $table->appendChild($document->createElement("div"));
        self::assertSame([$before1, $before2, $after], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before1, $before2, $tbody, $after], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testOneHTMLAndOneNamespacedTbodyChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before = $table->appendChild($document->createElement("tbody"));
        $after = $table->appendChild($document->createElementNS("x", "tbody"));
        self::assertSame([$before, $after], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before, $tbody, $after], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testTwoNestedTbodyChildNodes(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before1 = $table->appendChild($document->createElement("tbody"));
        $before2 = $before1->appendChild($document->createElement("tbody"));
        self::assertSame([$before1], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before1, $tbody], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATbodyNodeInsideATheadChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before1 = $table->appendChild($document->createElement("thead"));
        $before2 = $before1->appendChild($document->createElement("tbody"));
        self::assertSame([$before1], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before1, $tbody], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATbodyNodeInsideATfootChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before1 = $table->appendChild($document->createElement("tfoot"));
        $before2 = $before1->appendChild($document->createElement("tbody"));
        self::assertSame([$before1], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before1, $tbody], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATbodyInsideATheadChildNodeAfterATbodyChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before = $table->appendChild($document->createElement("tbody"));
        $after1 = $table->appendChild($document->createElement("thead"));
        $after2 = $after1->appendChild($document->createElement("tbody"));
        self::assertSame([$before, $after1], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before, $tbody, $after1], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testATbodyInsideATfootChildNodeAfterATbodyChildNode(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $before = $table->appendChild($document->createElement("tbody"));
        $after1 = $table->appendChild($document->createElement("tfoot"));
        $after2 = $after1->appendChild($document->createElement("tbody"));
        self::assertSame([$before, $after1], iterator_to_array($table->childNodes));

        $tbody = $table->createTBody();
        self::assertSame([$before, $tbody, $after1], iterator_to_array($table->childNodes));
        self::assertTBody($tbody);
    }

    public function testAPrefixedTableCreatesTbodyWithoutPrefix(): void
    {
        $document = $this->getHTMLDocument();
        $table = $document->createElement('table');
        $tbody = $table->appendChild($document->createElement("tbody"));
        self::assertNull($tbody->prefix);
    }
}
