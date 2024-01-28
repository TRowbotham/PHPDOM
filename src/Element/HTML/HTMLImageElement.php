<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\DynamicProperty\Getter;

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

    public function __set(string $name, $value): void
    {
        if ($name === 'src') {
            $this->attributeList->setAttrValue($name, (string) $value);

            return;
        }

        parent::__set($name, $value);
    }
}
