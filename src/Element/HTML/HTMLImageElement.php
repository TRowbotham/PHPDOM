<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-img-element
 */
class HTMLImageElement extends HTMLElement
{
    /**
     * @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-src
     */
    public string $src {
        get => $this->reflectUrlAttribute('src');
        set {
            $this->attributeList->setAttrValue('src', $value);
        }
    }
}
