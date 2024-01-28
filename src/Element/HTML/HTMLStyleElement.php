<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;

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
    #[Getter('media')]
    private function getMedia(): string
    {
        return $this->reflectStringAttributeValue('media');
    }

    #[Getter('type')]
    private function getType(): string
    {
        return $this->reflectStringAttributeValue('type');
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
