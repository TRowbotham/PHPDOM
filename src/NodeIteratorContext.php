<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use SplObjectStorage;

/**
 * @internal
 */
final class NodeIteratorContext
{
    /**
     * @var bool
     */
    public $pointerBeforeReferenceNode;

    /**
     * @var \Rowbot\DOM\Node
     */
    public $referenceNode;

    /**
     * @var \Rowbot\DOM\Node
     */
    public $root;

    /**
     * @var \SplObjectStorage<self, null>|null
     */
    private static $nodeIterators;

    public function __construct(Node $root)
    {
        $this->pointerBeforeReferenceNode = true;
        $this->referenceNode = $root;
        $this->root = $root;
    }

    /**
     * @see https://dom.spec.whatwg.org/#nodeiterator-pre-removing-steps
     */
    public function adjustIteratorPosition(Node $nodeToBeRemoved): void
    {
        // Step 1. If toBeRemovedNode is not an inclusive ancestor of nodeIterator’s reference, or toBeRemovedNode is
        // nodeIterator’s root, then return.
        if (!$nodeToBeRemoved->contains($this->referenceNode) || $nodeToBeRemoved === $this->root) {
            return;
        }

        // Step 2. If nodeIterator’s pointer before reference is true, then:
        if ($this->pointerBeforeReferenceNode) {
            // Step 2.1. Let next be toBeRemovedNode’s first following node that is an inclusive descendant of
            // nodeIterator’s root and is not an inclusive descendant of toBeRemovedNode, and null if there is no such
            // node.
            $next = null;
            $node = $nodeToBeRemoved;

            while ($node && !$node->nextSibling) {
                if ($node === $this->root) {
                    break;
                }

                $node = $node->parentNode;
            }

            if ($node && $node !== $this->root) {
                $next = $node->nextSibling;
            }

            // Step 2.2. If next is non-null, then set nodeIterator’s reference to next and return.
            if ($next) {
                $this->referenceNode = $next;

                return;
            }

            // Step 2.3. Otherwise, set nodeIterator’s pointer before reference to false.
            $this->pointerBeforeReferenceNode = false;

            // NOTE: Steps are not terminated here.
        }

        // Step 3. Set nodeIterator’s reference to toBeRemovedNode’s parent, if toBeRemovedNode’s previous sibling is
        // null, and to the inclusive descendant of toBeRemovedNode’s previous sibling that appears last in tree order
        // otherwise.
        $node = $nodeToBeRemoved->previousSibling;

        if (!$node) {
            $this->referenceNode = $nodeToBeRemoved->parentNode;

            return;
        }

        $this->referenceNode = $node;

        while (($node = $node->lastChild)) {
            $this->referenceNode = $node;
        }
    }

    /**
     * @return \SplObjectStorage<self, null>
     */
    public static function getIterators(): SplObjectStorage
    {
        if (self::$nodeIterators === null) {
            self::$nodeIterators = new SplObjectStorage();
        }

        return self::$nodeIterators;
    }
}
