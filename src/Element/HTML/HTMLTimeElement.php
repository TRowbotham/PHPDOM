<?php
namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-time-element
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

    public function __set(string $name, $value)
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
