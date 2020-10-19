<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Generator;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Namespaces;

/**
 * Represents the HTML table sectioning elements <thead>, <tfoot>, and <tbody>.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tbody-element
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-thead-element
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tfoot-element
 *
 * @property-read \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableRowElement> $rows
 */
class HTMLTableSectionElement extends HTMLElement
{
    /**
     * @var \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableRowElement>|null
     */
    private $rowsCollection;

    public function __get(string $name)
    {
        switch ($name) {
            case 'rows':
                if ($this->rowsCollection !== null) {
                    return $this->rowsCollection;
                }

                $this->rowsCollection = new HTMLCollection(
                    $this,
                    static function (self $root): Generator {
                        $node = $root->firstChild;

                        while ($node) {
                            if ($node instanceof HTMLTableRowElement) {
                                yield $node;
                            }

                            $node = $node->nextSibling;
                        }
                    }
                );

                return $this->rowsCollection;

            default:
                return parent::__get($name);
        }
    }

    /**
     * Creates a new tr element and inserts it into the table section at
     * the specified location. The newely created tr element is then returned.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tbody-insertrow
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError
     */
    public function insertRow(int $index = -1): HTMLTableRowElement
    {
        // 1. If index is less than −1 or greater than the number of elements in the rows
        // collection, throw an "IndexSizeError" DOMException.
        if ($index < -1) {
            throw new IndexSizeError();
        }

        $indexedRow = null;
        $node = $this->childNodes->first();
        $numRows = 0;

        while ($node) {
            if ($node instanceof HTMLTableRowElement) {
                if ($numRows === $index) {
                    $indexedRow = $node;
                }

                ++$numRows;
            }

            $node = $node->nextSibling;
        }

        if ($index > $numRows) {
            throw new IndexSizeError();
        }

        // 2. Let table row be the result of creating an element given this element's node document,
        // tr, and the HTML namespace.
        $tableRow = ElementFactory::create($this->nodeDocument, 'tr', Namespaces::HTML);

        // 3. If index is −1 or equal to the number of items in the rows collection, then append
        // table row to this element.
        if ($index === -1 || $index === $numRows) {
            // 5. Return table row.
            return $this->preinsertNode($tableRow);
        }

        // 4. Otherwise, insert table row as a child of this element, immediately before the indexth
        // tr element in the rows collection.
        $this->insertNode($tableRow, $indexedRow);

        // 5. Return table row.
        return $tableRow;
    }

    /**
     * Deletes the table row at the specified location.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tbody-deleterow
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index < -1 or $index >= number of table rows.
     */
    public function deleteRow(int $index): void
    {
        // 1. If index is less than −1 or greater than or equal to the number of elements in the
        // rows collection, then throw an "IndexSizeError" DOMException.
        if ($index < -1) {
            throw new IndexSizeError();
        }

        $node = $this->childNodes->first();
        $numRows = 0;
        $indexedRow = null;
        $lastRow = null;

        while ($node) {
            if ($node instanceof HTMLTableRowElement) {
                if ($numRows === $index) {
                    $indexedRow = $node;
                }

                ++$numRows;
                $lastRow = $node;
            }

            $node = $node->nextSibling;
        }

        if ($index >= $numRows) {
            throw new IndexSizeError();
        }

        // 2. If index is −1, then remove the last element in the rows collection from this element,
        // or do nothing if the rows collection is empty.
        if ($lastRow === null) {
            return;
        }

        if ($index === -1) {
            $lastRow->removeNode();

            return;
        }

        // 3. Otherwise, remove the indexth element in the rows collection from this element.
        assert($indexedRow !== null);
        $indexedRow->removeNode();
    }
}
