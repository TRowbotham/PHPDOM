<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\DynamicProperty\Setter;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Utils;

/**
 * @see https://html.spec.whatwg.org/multipage/embedded-content.html#the-img-element
 */
class HTMLImageElement extends HTMLElement
{
    #[Getter('src')]
    private function getSrc(): string
    {
        return $this->reflectUrlAttribute('src');
    }

    #[Setter('src')]
    private function setSrc(mixed $value): void
    {
        if (!Utils::isStringable($value)) {
            throw new TypeError();
        }

        $this->attributeList->setAttrValue('src', (string) $value);
    }
}
