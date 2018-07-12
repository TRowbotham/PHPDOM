<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DOMTokenList;

use function is_int;

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
    private $colSpan;
    private $headers;
    private $rowSpan;

    protected function __construct()
    {
        parent::__construct();

        $this->colSpan = 1;
        $this->headers = new DOMTokenList($this, 'headers');
        $this->rowSpan = 1;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'cellIndex':
                if ($this->parentNode instanceof HTMLTableRowElement) {
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
                return $this->colSpan;

            case 'headers':
                return $this->headers->value;

            case 'rowSpan':
                return $this->rowSpan;

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'colspan':
                if (!is_int((int)$value)) {
                    break;
                }

                $this->colSpan = (int)$value;
                $this->updateAttributeOnPropertyChange($name, $value);

                break;

            case 'rowspan':
                if (!is_int((int)$value)) {
                    break;
                }

                $this->rowSpan = (int)$value;
                $this->updateAttributeOnPropertyChange($name, $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
