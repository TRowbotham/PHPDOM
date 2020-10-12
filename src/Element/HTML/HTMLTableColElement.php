<?php
namespace Rowbot\DOM\Element\HTML;

/**
 * @see https://html.spec.whatwg.org/#the-colgroup-element
 * @see https://html.spec.whatwg.org/#the-col-element
 */
class HTMLTableColElement extends HTMLElement
{
    public function __get(string $name)
    {
        switch ($name) {
            case 'span':
                return $this->reflectClampedUnsignedLongAttributeValue('span', 1, 1000, 1);

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value)
    {
        switch ($name) {
            case 'span':
                $this->setLongAttributeValue('span', $value, self::UNSIGNED_LONG);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
