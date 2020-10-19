<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * Represents the HTML <td> and <th> elements respectively.
 *
 * @see https://html.spec.whatwg.org/multipage/tables.html#htmltablecellelement
 * @see https://html.spec.whatwg.org/multipage/obsolete.html#HTMLTableCellElement-partial
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-td-element
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-th-element
 *
 * @property int $colSpan Represents the number of columns this row must span. Default value is 1. Reflects the value of
 *                        the colspan attribute.
 * @property int $rowSpan Represents the number of rows this column must span. Default value is 1. Reflects the value of
 *                        the rowspan attribute.
 *
 * @property-read int    $cellIndex Returns the position of the cell in the row's cells list. Returns -1 if the element
 *                                  isn't in a row.
 * @property-read string $headers   A list of ids of th elements that represents <th> elements associated with this
 *                                  cell.
 */
class HTMLTableCellElement extends HTMLElement
{
    public function __get(string $name)
    {
        switch ($name) {
            case 'cellIndex':
                // The cellIndex IDL attribute must, if the element has a parent tr element, return
                // the index of the cell's element in the parent element's cells collection. If
                // there is no such parent element, then the attribute must return −1.
                if (!$this->parentNode instanceof HTMLTableRowElement) {
                    return -1;
                }

                $node = $this->previousSibling;
                $index = 0;

                while ($node) {
                    if ($node instanceof self) {
                        ++$index;
                    }

                    $node = $node->previousSibling;
                }

                return $index;

            case 'colSpan':
                return $this->reflectClampedUnsignedLongAttributeValue('colspan', 1, 1000, 1);

            case 'headers':
                return $this->reflectStringAttributeValue('headers');

            case 'rowSpan':
                return $this->reflectClampedUnsignedLongAttributeValue('rowspan', 0, 65534, 1);

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'colSpan':
                $this->setLongAttributeValue('colspan', $value, self::UNSIGNED_LONG);

                break;

            case 'rowSpan':
                $this->setLongAttributeValue('rowspan', $value, self::UNSIGNED_LONG);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
