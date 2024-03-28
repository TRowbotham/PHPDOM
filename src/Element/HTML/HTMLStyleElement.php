<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * Represents the HTML <style> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-style-element
 */
class HTMLStyleElement extends HTMLElement
{
    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-style-media
     */
    public string $media {
        get => $this->reflectStringAttributeValue('media');
        set {
            $this->attributeList->setAttrValue('media', $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/obsolete.html#dom-style-type
     */
    public string $type {
        get => $this->reflectStringAttributeValue('type');
        set {
            $this->attributeList->setAttrValue('type', $value);
        }
    }
}
