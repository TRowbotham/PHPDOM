<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-time-element
 *
 * @property string $dateTime
 */
class HTMLTimeElement extends HTMLElement
{
    #[Getter('dateTime')]
    private function getDateTime(): string
    {
        return $this->reflectStringAttributeValue('datetime');
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
