<?php
namespace phpjs\elements\html;

use phpjs\elements\ElementFactory;
use phpjs\exceptions\IndexSizeError;
use phpjs\Namespaces;
use phpjs\NodeFilter;

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

    public function __get($aName)
    {
        switch ($aName) {
            case 'cells':
                $cells = [];

                foreach ($this->mChildNodes as $cell) {
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
                    function ($aNode) {
                        return $aNode instanceof HTMLTableRowElement ?
                            NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
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
                    function ($aNode) {
                        return $aNode instanceof HTMLTableRowElement ?
                            NodeFilter::FILTER_ACCEPT : NodeFilter::FILTER_SKIP;
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
                return parent::__get($aName);
        }
    }

    /**
     * Inserts a new cell at the given index.
     *
     * @param int $aIndex A positive integer of the index position where the
     *     cell should be inserted.
     *
     * @return HTMLTableCellElement
     *
     * @throws IndexSizeError If $aIndex < -1 or >= the number of cells in the
     *     collection.
     */
    public function insertCell($aIndex = -1)
    {
        $cells = $this->cells;
        $numCells = count($cells);

        if ($aIndex < -1 || $aIndex > $numCells) {
            throw new IndexSizeError();
        }

        $td = ElementFactory::create(
            $this->nodeDocument,
            'td',
            Namespaces::HTML
        );

        if ($aIndex == -1 || $aIndex == $numCells) {
            $this->appendChild($td);
        } else {
            $cells[$aIndex]->before($td);
        }

        return $td;
    }

    /**
     * Removes the cell at the given index from its parent.
     *
     * @param int $aIndex The index of the cell to be removed.
     *
     * @throws IndexSizeError If $aIndex < 0 or >= the number of cells in the
     *     collection.
     */
    public function deleteCell($aIndex)
    {
        $cells = [];

        foreach ($this->mChildNodes as $cell) {
            if ($cell instanceof HTMLTableCellElement) {
                $cells[] = $cell;
            }
        }

        if ($aIndex < 0 || $aIndex >= count($cells)) {
            throw new IndexSizeError();
        }

        $cells[$aIndex]->remove();
    }
}
