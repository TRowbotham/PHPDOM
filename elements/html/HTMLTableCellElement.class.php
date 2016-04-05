<?php
namespace phpjs\elements\html;

use phpjs\DOMTokenList;

/**
 * A common class from which the HTMLTableDataCellElement and
 * HTMLTableHeaderCellElement classes derive from.  The HTMLTableDataCellElement
 * and HTMLTableHeaderCellElement classes represent the HTML <td> and <th>
 * elements respectively.
 *
 * @see https://html.spec.whatwg.org/#htmltablecellelement
 *
 * @property int $colSpan Represents the number of columns this row must span.
 *     Default value is 1.  Reflects the value of the colspan attribute.
 *
 * @property int $rowSpan Represents the number of rows this column must span.
 *     Default value is 1.  Reflects the value of the rowspan attribute.
 *
 * @property-read int $cellIndex Returns the position of the cell in the row's
 *     cells list.  Returns -1 if the element isn't in a row.
 *
 * @property-read string $headers A list of ids of th elements that represents
 *     th elements associated with this cell.
 */
class HTMLTableCellElement extends HTMLElement
{
    private $mColSpan;
    private $mHeaders;
    private $mRowSpan;

    protected function __construct()
    {
        parent::__construct();

        $this->mColSpan = 1;
        $this->mHeaders = new DOMTokenList($this, 'headers');
        $this->mRowSpan = 1;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'cellIndex':
                if ($this->mParentNode instanceof HTMLTableRowElement) {
                    $node = $this;
                    $index = 0;

                    while ($node) {
                        $count++;
                        $node = $node->previousSibling;
                    }

                    return $count;
                }

                return -1;

            case 'colSpan':
                return $this->mColSpan;

            case 'headers':
                return $this->mHeaders->value;

            case 'rowSpan':
                return $this->mRowSpan;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'colspan':
                if (!is_int((int)$aValue)) {
                    break;
                }

                $this->mColSpan = (int)$aValue;
                $this->updateAttributeOnPropertyChange($aName, $aValue);

                break;

            case 'rowspan':
                if (!is_int((int)$aValue)) {
                    break;
                }

                $this->mRowSpan = (int)$aValue;
                $this->updateAttributeOnPropertyChange($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}
