<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

/**
 * @see https://dom.spec.whatwg.org/#interface-nondocumenttypechildnode
 */
trait NonDocumentTypeChildNode
{
    /**
     * Gets the previous element sibling.
     *
     * @see https://dom.spec.whatwg.org/#dom-nondocumenttypechildnode-previouselementsibling
     */
    public ?Element $previousElementSibling {
        get {
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

    /**
     * Gets the next element sibling.
     *
     * @see https://dom.spec.whatwg.org/#dom-nondocumenttypechildnode-nextelementsibling
     */
    public ?Element $nextElementSibling {
        get {
            $node = $this->_nextSibling;

            while ($node) {
                if ($node instanceof Element) {
                    return $node;
                }

                $node = $node->_nextSibling;
            }

            return null;
        }
    }
}
