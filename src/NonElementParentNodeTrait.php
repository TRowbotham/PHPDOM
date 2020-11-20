<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

trait NonElementParentNodeTrait
{
    /**
     * @see @see https://dom.spec.whatwg.org/#dom-nonelementparentnode-getelementbyid
     */
    public function getElementById(string $elementId): ?Element
    {
        if ($elementId === '') {
            return null;
        }

        $node = $this->nextNode($this);

        while ($node) {
            if ($node instanceof Element && $node->id === $elementId) {
                return $node;
            }

            $node = $node->nextNode($this);
        }

        return $node;
    }
}
