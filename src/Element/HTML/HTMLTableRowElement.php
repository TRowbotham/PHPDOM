<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Generator;
use Rowbot\DOM\Document;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Namespaces;

use function assert;

/**
 * Represents the HTML table row element <tr>.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tr-element
 */
class HTMLTableRowElement extends HTMLElement
{
    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tr-rowindex
     */
    public int $rowIndex {
        get {
            $parentIsTable = $this->parentNode instanceof HTMLTableElement;

            if (
                !$parentIsTable
                && (
                    !$this->parentNode instanceof HTMLTableSectionElement
                    || !$this->parentNode->parentNode instanceof HTMLTableElement
                )
            ) {
                return -1;
            }

            $parentTable = $parentIsTable
                ? $this->parentNode
                : $this->parentNode->parentNode;
            $rows = $parentTable->rows->getIterator();
            $rows->rewind();
            $index = 0;

            while ($rows->valid()) {
                if ($rows->current() === $this) {
                    break;
                }

                ++$index;
                $rows->next();
            }

            return $index;
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tr-sectionrowindex
     */
    public int $sectionRowIndex {
        get {
            if (
                !$this->parentNode instanceof HTMLTableElement
                && !$this->parentNode instanceof HTMLTableSectionElement
            ) {
                return -1;
            }

            $index = 0;
            $rows = $this->parentNode->rows->getIterator();
            $rows->rewind();

            while ($rows->valid()) {
                if ($rows->current() === $this) {
                    break;
                }

                ++$index;
                $rows->next();
            }

            return $index;
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tr-cells
     */
    public HTMLCollection $cells {
        get => $this->cells ??= new HTMLCollection(
            $this,
            static function (self $root): Generator {
                $node = $root->firstChild;

                while ($node) {
                    if ($node instanceof HTMLTableCellElement) {
                        yield $node;
                    }

                    $node = $node->nextSibling;
                }
            }
        );
    }

    public function __construct(Document $document, string $localName, ?string $namespace, ?string $prefix = null)
    {
        parent::__construct($document, $localName, $namespace, $prefix);
    }

    /**
     * Inserts a new cell at the given index.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tr-insertcell
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index < -1 or >= the number of cells in the collection.
     */
    public function insertCell(int $index = -1): HTMLTableCellElement
    {
        // 1. If index is less than −1 or greater than the number of elements in the cells
        // collection, then throw an "IndexSizeError" DOMException.
        if ($index < -1) {
            throw new IndexSizeError();
        }

        $node = $this->childNodes_->first();
        $numCells = 0;
        $indexedCell = null;

        while ($node) {
            if ($node instanceof HTMLTableCellElement) {
                if ($numCells === $index) {
                    $indexedCell = $node;
                }

                ++$numCells;
            }

            $node = $node->nextSibling;
        }

        if ($index > $numCells) {
            throw new IndexSizeError();
        }

        // 2. Let table cell be the result of creating an element given this tr element's node
        // document, td, and the HTML namespace.
        $tableCell = ElementFactory::create($this->nodeDocument, 'td', Namespaces::HTML);

        // 3. If index is equal to −1 or equal to the number of items in cells collection, then
        // append table cell to this tr element.
        if ($index === -1 || $index === $numCells) {
            // 5. Return table cell.
            return $this->preinsertNode($tableCell);
        }

        // 4. Otherwise, insert table cell as a child of this tr element, immediately before the
        // indexth td or th element in the cells collection.
        $this->insertNode($tableCell, $indexedCell);

        return $tableCell;
    }

    /**
     * Removes the cell at the given index from its parent.
     *
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tr-deletecell
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index < 0 or >= the number of cells in the collection.
     */
    public function deleteCell(int $index): void
    {
        // 1. If index is less than −1 or greater than or equal to the number of elements in the
        // cells collection, then throw an "IndexSizeError" DOMException.
        if ($index < -1) {
            throw new IndexSizeError();
        }

        $node = $this->childNodes_->first();
        $indexedCell = null;
        $lastCell = null;
        $numCells = 0;

        while ($node) {
            if ($node instanceof HTMLTableCellElement) {
                if ($index === $numCells) {
                    $indexedCell = $node;
                }

                $lastCell = $node;
                ++$numCells;
            }

            $node = $node->nextSibling;
        }

        if ($index >= $numCells) {
            throw new IndexSizeError();
        }

        // 2. If index is −1, then remove the last element in the cells collection from its parent,
        // or do nothing if the cells collection is empty.
        if ($lastCell === null) {
            return;
        }

        if ($index === -1) {
            $lastCell->removeNode();

            return;
        }

        // 3. Otherwise, remove the indexth element in the cells collection from its parent.
        assert($indexedCell !== null);
        $indexedCell->removeNode();
    }
}
