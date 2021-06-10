<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\SVG;

use Rowbot\DOM\DOMStringMap;
use Rowbot\DOM\Element\Element;

/**
 * @see https://svgwg.org/svg2-draft/types.html#InterfaceSVGElement
 */
class SVGElement extends Element
{
    public function __get(string $name)
    {
        if ($name === 'dataset') {
            return new DOMStringMap($this);
        }

        return parent::__get($name);
    }
}
