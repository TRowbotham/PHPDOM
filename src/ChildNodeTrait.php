<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use function in_array;

/**
 * This trait is meant to be used to fullfill the requirements of the ChildNode interface in the
 * context of a Node object.
 */
trait ChildNodeTrait
{
    use ChildOrParentNode;

    /**
     * @see https://dom.spec.whatwg.org/#dom-childnode-after
     */
    public function after(...$nodes): void
    {
        // 1. Let parent be this’s parent.
        $parent = $this->parentNode;

        // 2. If parent is null, then return.
        if (!$parent) {
            return;
        }

        // 3. Let viableNextSibling be this’s first following sibling not in nodes, and null
        // otherwise.
        $viableNextSibling = $this->nextSibling;

        while ($viableNextSibling) {
            if (!in_array($viableNextSibling, $nodes, true)) {
                break;
            }

            $viableNextSibling = $viableNextSibling->nextSibling;
        }

        // 4. Let node be the result of converting nodes into a node, given nodes and this’s node
        // document.
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        // 5. Pre-insert node into parent before viableNextSibling.
        $parent->preinsertNode($node, $viableNextSibling);
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-childnode-before
     */
    public function before(...$nodes): void
    {
        // 1. Let parent be this’s parent.
        $parent = $this->parentNode;

        // 2. If parent is null, then return.
        if (!$parent) {
            return;
        }

        // 3. Let viablePreviousSibling be this’s first preceding sibling not in nodes, and null
        // otherwise.
        $viablePreviousSibling = $this->previousSibling;

        while ($viablePreviousSibling) {
            if (!in_array($viablePreviousSibling, $nodes, true)) {
                break;
            }

            $viablePreviousSibling = $viablePreviousSibling->previousSibling;
        }

        // 4. Let node be the result of converting nodes into a node, given nodes and this’s node
        // document.
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        // 5. If viablePreviousSibling is null, set it to parent’s first child, and to
        // viablePreviousSibling’s next sibling otherwise.
        $viablePreviousSibling = $viablePreviousSibling
            ? $viablePreviousSibling->nextSibling
            : $parent->firstChild;

        // 6. Pre-insert node into parent before viablePreviousSibling.
        $parent->preinsertNode($node, $viablePreviousSibling);
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-childnode-remove
     */
    public function remove(): void
    {
        if (!$this->parentNode) {
            return;
        }

        $this->removeNode();
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-childnode-replacewith
     */
    public function replaceWith(...$nodes): void
    {
        // 1. Let parent be this’s parent.
        $parent = $this->parentNode;

        // 2. If parent is null, then return.
        if (!$parent) {
            return;
        }

        // 3. Let viableNextSibling be this’s first following sibling not in nodes, and null
        // otherwise.
        $viableNextSibling = $this->nextSibling;

        while ($viableNextSibling) {
            if (!in_array($viableNextSibling, $nodes, true)) {
                break;
            }

            $viableNextSibling = $viableNextSibling->nextSibling;
        }

        // 4. Let node be the result of converting nodes into a node, given nodes and this’s node
        // document.
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        // 5. If this’s parent is parent, replace this with node within parent.
        if ($this->parentNode === $parent) {
            $parent->replaceNode($node, $this);

            return;
        }

        // 6. Otherwise, pre-insert node into parent before viableNextSibling.
        $parent->preinsertNode($node, $viableNextSibling);
    }
}
