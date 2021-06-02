<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-img-element
 */
class HTMLImageElement extends HTMLElement
{
    public function __get(string $name)
    {
        if ($name === 'src') {
            return $this->reflectUrlAttribute($name);
        }

        return parent::__get($name);
    }

    public function __set(string $name, $value): void
    {
        if ($name === 'src') {
            $this->attributeList->setAttrValue($name, (string) $value);

            return;
        }

        parent::__set($name, $value);
    }
}
