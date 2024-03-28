<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * Represents the HTML <meta> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-meta-element
 */
class HTMLMetaElement extends HTMLElement
{
    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-meta-name
     */
    public string $name {
        get => $this->reflectStringAttributeValue('name');
        set {
            $this->attributeList->setAttrValue('name', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-meta-httpequiv
     */
    public string $httpEquiv {
        get => $this->reflectStringAttributeValue('http-equiv');
        set {
            $this->attributeList->setAttrValue('http-equiv', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-meta-content
     */
    public string $content {
        get => $this->reflectStringAttributeValue('content');
        set {
            $this->attributeList->setAttrValue('content', $value);
        }
    }
}
