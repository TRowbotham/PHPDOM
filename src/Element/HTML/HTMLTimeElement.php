<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-time-element
 *
 * @property string $dateTime
 */
class HTMLTimeElement extends HTMLElement
{
    public function __get(string $name)
    {
        switch ($name) {
            case 'dateTime':
                return $this->reflectStringAttributeValue('datetime');

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'dateTime':
                $this->attributeList->setAttrValue('datetime', (string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
