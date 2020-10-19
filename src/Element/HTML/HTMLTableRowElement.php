<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\TreeWalker;

use function count;

/**
 * Represents the HTML table row element <tr>.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-tr-element
 *
 * @property-read \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableCellElement> $cells
 * @property-read int                                                                       $rowIndex
 * @property-read int                                                                       $sectionRowIndex
 */
class HTMLTableRowElement extends HTMLElement
{
    /**
     * @var \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\HTML\HTMLTableCellElement>|null
     */
    private $cellsCollection;

    public function __get(string $name)
    {
        switch ($name) {
            case 'cells':
                if ($this->cellsCollection !== null) {
                    return $this->cellsCollection;
                }

                $this->cellsCollection = new HTMLCollection(
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

                return $this->cellsCollection;

            case 'rowIndex':
                $parentTable = $this->parentNode;
                $count = 0;

                while ($parentTable) {
                    if ($parentTable instanceof HTMLTableElement) {
                        break;
                    }

                    $parentTable = $parentTable->parentNode;
                }

                if (!$parentTable) {
                    return -1;
                }

                $tw = new TreeWalker(
                    $parentTable,
                    NodeFilter::SHOW_ELEMENT,
                    static function (Element $node): int {
                        if ($node instanceof HTMLTableRowElement) {
                            return NodeFilter::FILTER_ACCEPT;
                        }

                        return NodeFilter::FILTER_SKIP;
                    }
                );

                while ($row = $tw->nextNode()) {
                    if ($row === $this) {
                        break;
                    }

                    $count++;
                }

                return $count;

            case 'sectionRowIndex':
                // The sectionRowIndex attribute must, if this element has a parent table, tbody,
                // thead, or tfoot element, return the index of the tr element in the parent
                // element's rows collection (for tables, that's HTMLTableElement's rows collection;
                // for table sections, that's HTMLTableSectionElement's rows collection). If there
                // is no such parent element, then the attribute must return −1.
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

            default:
                return parent::__get($name);
        }
    }

    /**
     * Inserts a new cell at the given index.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index < -1 or >= the number of cells in the collection.
     */
    public function insertCell(int $index = -1): HTMLTableCellElement
    {
        $cells = $this->cells;
        $numCells = count($cells);

        if ($index < -1 || $index > $numCells) {
            throw new IndexSizeError();
        }

        $td = ElementFactory::create(
            $this->nodeDocument,
            'td',
            Namespaces::HTML
        );

        if ($index === -1 || $index === $numCells) {
            $this->appendChild($td);
        } else {
            $cells[$index]->before($td);
        }

        return $td;
    }

    /**
     * Removes the cell at the given index from its parent.
     *
     * @throws \Rowbot\DOM\Exception\IndexSizeError If $index < 0 or >= the number of cells in the collection.
     */
    public function deleteCell(int $index): void
    {
        $cells = [];

        foreach ($this->childNodes as $cell) {
            if ($cell instanceof HTMLTableCellElement) {
                $cells[] = $cell;
            }
        }

        if ($index < 0 || $index >= count($cells)) {
            throw new IndexSizeError();
        }

        $cells[$index]->remove();
    }
}
