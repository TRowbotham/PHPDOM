<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\DOMStringMap;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#htmlorsvgelement
 */
trait HTMLOrSVGElement
{
    /**
     * @var \Rowbot\DOM\DOMStringMap|null
     */
    private $dataset;

    protected function getDataset(): DOMStringMap
    {
        if ($this->dataset === null) {
            $this->dataset = new DOMStringMap($this);
        }

        return $this->dataset;
    }
}
