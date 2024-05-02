<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\DOMStringMap;

/**
 * @see https://html.spec.whatwg.org/multipage/dom.html#htmlorsvgelement
 */
trait HTMLOrSVGElement
{
    public DOMStringMap $dataset {
        get => $this->_dataset ??= new DOMStringMap($this);
    }

    private ?DOMStringMap $_dataset = null;

    private function onCloneHTMLOrSVGElement(): void
    {
        $this->_dataset = null;
    }
}
