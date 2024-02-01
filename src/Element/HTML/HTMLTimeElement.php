<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Utils;

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

    #[Setter('dateTime')]
    private function setDateTime(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('datetime', (string) $value);
    }
}
