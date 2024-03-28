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
 */
class HTMLTableCellElement extends HTMLElement
{
    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tdth-colspan
     */
    public int $colSpan {
        get => $this->reflectClampedUnsignedLongAttributeValue('colspan', 1, 1000, 1);
        set {
            $this->setLongAttributeValue('colspan', $value, self::UNSIGNED_LONG);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tdth-rowspan
     */
    public int $rowSpan {
        get => $this->reflectClampedUnsignedLongAttributeValue('rowspan', 0, 65534, 1);
        set {
            $this->setLongAttributeValue('rowspan', $value, self::UNSIGNED_LONG);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tdth-headers
     */
    public string $headers {
        get => $this->reflectStringAttributeValue('headers');
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-tdth-cellindex
     */
    public int $cellIndex {
        get {
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
        }
    }
}
