<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Utils;

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

    #[Setter('media')]
    private function setMedia(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('media', (string) $value);
    }

    #[Setter('type')]
    private function setType(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('type', (string) $value);
    }
}
