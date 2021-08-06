<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\DOMStringMap;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#htmlorsvgelement
 */
trait HTMLOrSVGElement
{
    private ?DOMStringMap $dataset = null;

    protected function getDataset(): DOMStringMap
    {
        return $this->dataset ??= new DOMStringMap($this);
    }
}
