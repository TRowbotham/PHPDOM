<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\SVG;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTMLOrSVGElement;

/**
 * @see https://svgwg.org/svg2-draft/types.html#InterfaceSVGElement
 */
class SVGElement extends Element
{
    use HTMLOrSVGElement;

    protected function __clone(): void
    {
        parent::__clone();

        $this->onCloneHTMLOrSVGElement();
    }
}
