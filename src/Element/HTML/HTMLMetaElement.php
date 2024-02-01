<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Utils;

/**
 * Represents the HTML <meta> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-meta-element
 *
 * @property string $content   Reflects the value of the HTML content attribute. Contains the value part of a name =>
 *                             value pair when the name attribute is present.
 * @property string $httpEquiv Reflects the value of the HTML http-equiv attribute.
 * @property string $name      Reflects the value of the HTML name attribute.
 */
class HTMLMetaElement extends HTMLElement
{
    #[Getter('content')]
    private function getContent(): string
    {
        return $this->reflectStringAttributeValue('content');
    }

    #[Getter('httpEquiv')]
    private function getHttpEquiv(): string
    {
        return $this->reflectStringAttributeValue('http-equiv');
    }

    #[Getter('name')]
    private function getName(): string
    {
        return $this->reflectStringAttributeValue('name');
    }

    #[Setter('content')]
    private function setContent(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('content', (string) $value);
    }

    #[Setter('http-equiv')]
    private function setHttpEquiv(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('http-equiv', (string) $value);
    }

    #[Setter('name')]
    private function setName(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('name', (string) $value);
    }
}
