<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\ElementFactory;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\NodeFilter;

/**
 * Represents the HTML table row element <tr>.
 *
 * @link https://html.spec.whatwg.org/#the-tr-element
 *
 * @property-read HTMLTableCellElement[] $cells Returns a collection of all <td>
 *     and <th> elements in a row.
 *
 * @property-read int $rowIndex Returns the position of the row relative to it's
 *     containing table.  Returns -1 if the row isn't in a table.
 *
 * @property-read int $sectionRowIndex Returns the position of the row relative
 *     to it's containing table section element. Returns -1 if the row isn't in
 *     a table section.
 */
class HTMLTableRowElement extends HTMLElement
{
    protected function __construct()
    {
        parent::__construct();
    }

    public function __get($name)
    {
        switch ($name) {
            case 'cells':
                $cells = [];

                foreach ($this->childNodes as $cell) {
                    if ($cell instanceof HTMLTableCellElement) {
                        $cells[] = $cell;
                    }
                }

                return $cells;

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
                    function ($node) {
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
                $parent = $this->parentNode;
                $count = 0;

                if (!($parent instanceof HTMLTableSectionElement)) {
                    return -1;
                }

                $tw = new TreeWalker(
                    $parent,
                    NodeFilter::SHOW_ELEMENT,
                    function ($node) {
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

            default:
                return parent::__get($name);
        }
    }

    /**
     * Inserts a new cell at the given index.
     *
     * @param int $index A positive integer of the index position where the
     *     cell should be inserted.
     *
     * @return HTMLTableCellElement
     *
     * @throws IndexSizeError If $index < -1 or >= the number of cells in the
     *     collection.
     */
    public function insertCell($index = -1)
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

        if ($index == -1 || $index == $numCells) {
            $this->appendChild($td);
        } else {
            $cells[$index]->before($td);
        }

        return $td;
    }

    /**
     * Removes the cell at the given index from its parent.
     *
     * @param int $index The index of the cell to be removed.
     *
     * @throws IndexSizeError If $index < 0 or >= the number of cells in the
     *     collection.
     */
    public function deleteCell($index)
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
