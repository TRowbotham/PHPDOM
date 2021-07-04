<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\SVG;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTMLOrSVGElement;

/**
 * @see https://svgwg.org/svg2-draft/types.html#InterfaceSVGElement
 *
 * @property \Rowbot\DOM\DOMStringMap $dataset
 */
class SVGElement extends Element
{
    use HTMLOrSVGElement;

    public function __get(string $name)
    {
        if ($name === 'dataset') {
            return $this->getDataset();
        }

        return parent::__get($name);
    }
}
