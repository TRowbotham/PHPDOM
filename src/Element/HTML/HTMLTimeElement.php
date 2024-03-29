<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-time-element
 */
class HTMLTimeElement extends HTMLElement
{
    /**
     * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#dom-time-datetime
     */
    public string $dateTime {
        get => $this->reflectStringAttributeValue('datetime');
        set {
            $this->attributeList->setAttrValue('datetime', $value);
        }
    }
}
