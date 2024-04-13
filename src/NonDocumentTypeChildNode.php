<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\DynamicProperty\Getter;
use Rowbot\DOM\Element\Element;

/**
 * @see https://dom.spec.whatwg.org/#interface-nondocumenttypechildnode
 */
trait NonDocumentTypeChildNode
{
    /**
     * Gets the next element sibling.
     *
     * @see https://dom.spec.whatwg.org/#dom-nondocumenttypechildnode-previouselementsibling
     */
    #[Getter('nextElementSibling')]
    private function getNextElementSibling(): ?Element
    {
        $node = $this->_nextSibling;

        while ($node) {
            if ($node instanceof Element) {
                return $node;
            }

            $node = $node->_nextSibling;
        }

        return null;
    }

    /**
     * Gets the previous element sibling.
     *
     * @see https://dom.spec.whatwg.org/#dom-nondocumenttypechildnode-nextelementsibling
     */
    #[Getter('previousElementSibling')]
    private function getPreviousElementSibling(): ?Element
    {
        $node = $this->_previousSibling;

        while ($node) {
            if ($node instanceof Element) {
                return $node;
            }

            $node = $node->_previousSibling;
        }

        return null;
    }
}
