<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-colgroup-element
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-col-element
 */
class HTMLTableColElement extends HTMLElement
{
    /**
     * @see https://html.spec.whatwg.org/multipage/tables.html#dom-colgroup-span
     */
    public int $span {
        get => $this->reflectClampedUnsignedLongAttributeValue('span', 1, 1000, 1);
        set {
            $this->setLongAttributeValue('span', $value, self::UNSIGNED_LONG);
        }
    }
}
