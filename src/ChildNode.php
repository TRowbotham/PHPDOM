<?php
namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-childnode
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ChildNode
 */
trait ChildNode
{
    use ChildOrParentNode;

    /**
     * Inserts any number of Node objects or strings after this ChildNode.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-after
     *
     * @param Node|string ...$nodes A set of Node objects or strings to be
     *     inserted.
     */
    public function after(...$nodes)
    {
        $parent = $this->mParentNode;

        if (!$parent) {
            return;
        }

        $viableNextSibling = $this->nextSibling;

        while ($viableNextSibling) {
            if (!in_array($viableNextSibling, $nodes, true)) {
                break;
            }

            $viableNextSibling = $viableNextSibling->nextSibling;
        }

        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);
        $parent->preinsertNode($node, $viableNextSibling);
    }

    /**
     * Inserts any number of Node objects or strings before this ChildNode.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-before
     *
     * @param Node|string ...$nodes A set of Node objects or strings to be
     *     inserted.
     */
    public function before(...$nodes)
    {
        $parent = $this->mParentNode;

        if (!$parent) {
            return;
        }

        $viablePreviousSibling = $this->previousSibling;

        while ($viablePreviousSibling) {
            if (!in_array($viablePreviousSibling, $nodes, true)) {
                break;
            }

            $viablePreviousSibling = $viablePreviousSibling->previousSibling;
        }

        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);
        $viablePreviousSibling = $viablePreviousSibling
            ? $viablePreviousSibling->nextSibling
            : $parent->firstChild;
        $parent->preinsertNode($node, $viablePreviousSibling);
    }

    /**
     * Removes this ChildNode from its ParentNode.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-remove
     */
    public function remove()
    {
        if (!$this->mParentNode) {
            return;
        }

        $this->mParentNode->removeNode($this);
    }

    /**
     * Replaces this ChildNode with any number of Node objects or strings.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-replacewith
     *
     * @param Node|string ...$nodes A set of Node objects or strings to be
     *     inserted in place of this ChildNode.
     */
    public function replaceWith(...$nodes)
    {
        $parent = $this->mParentNode;

        if (!$parent) {
            return;
        }

        $viableNextSibling = $this->nextSibling;

        while ($viableNextSibling) {
            if (!in_array($viableNextSibling, $nodes, true)) {
                break;
            }

            $viableNextSibling = $viableNextSibling->nextSibling;
        }

        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        if ($this->mParentNode === $parent) {
            $parent->replaceNode($node, $this);
        } else {
            $parent->preinsertNode($node, $viableNextSibling);
        }
    }
}
