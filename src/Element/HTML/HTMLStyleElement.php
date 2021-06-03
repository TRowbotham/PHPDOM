<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * Represents the HTML <style> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-style-element
 *
 * @property string $media  Reflects the HTML media attribute. This accepts a valid media query to instruct the browser
 *                          on when this resource should apply to the document.
 * @property string $type   Reflects the HTML type attribute, which hints to the browser what the content's MIME type
 *                          is. This property defaults to text/css.
 */
class HTMLStyleElement extends HTMLElement
{
    public function __get(string $name)
    {
        switch ($name) {
            case 'media':
                return $this->reflectStringAttributeValue($name);

            case 'type':
                return $this->reflectStringAttributeValue($name);

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'media':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'type':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
