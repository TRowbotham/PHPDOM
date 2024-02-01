<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;

/**
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-colgroup-element
 * @see https://html.spec.whatwg.org/multipage/tables.html#the-col-element
 */
class HTMLTableColElement extends HTMLElement
{
    #[Getter('span')]
    private function getSpan(): int
    {
        return $this->reflectClampedUnsignedLongAttributeValue('span', 1, 1000, 1);
    }

    #[Setter('span')]
    private function setSpan(mixed $value): void
    {
        $this->setLongAttributeValue('span', $value, self::UNSIGNED_LONG);
    }
}
