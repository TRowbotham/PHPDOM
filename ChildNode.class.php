<?php
namespace phpjs;

/**
 * @see https://dom.spec.whatwg.org/#interface-childnode
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ChildNode
 */
trait ChildNode
{
    /**
     * Inserts any number of Node objects or strings after this ChildNode.
     *
     * @link https://dom.spec.whatwg.org/#dom-childnode-after
     *
     * @param Node|string ...$aNodes A set of Node objects or strings to be
     *     inserted.
     */
    public function after()
    {
        $parent = $this->mParentNode;
        $nodes = func_get_args();

        if (!$parent) {
            return;
        }

        $viableNextSibling = $this->mNextSibling;

        while ($viableNextSibling) {
            if (!in_array($viableNextSibling, $nodes)) {
                break;
            }

            $viableNextSibling = $viableNextSibling->nextSibling;
        }

        $node = Node::convertNodesToNode($nodes, $this->mOwnerDocument);
        $this->preinsertNode($node, $viableNextSibling);
    }

    /**
     * Inserts any number of Node objects or strings before this ChildNode.
     *
     * @link https://dom.spec.whatwg.org/#dom-childnode-before
     *
     * @param  Node|string ...$aNodes A set of Node objects or strings to be
     *     inserted.
     */
    public function before()
    {
        $parent = $this->mParentNode;
        $nodes = func_get_args();

        if (!$parent) {
            return;
        }

        $viablePreviousSibling = $this->mPreviousSibling;

        while ($viablePreviousSibling) {
            if (!in_array($viablePreviousSibling, $nodes)) {
                break;
            }

            $viablePreviousSibling = $viablePreviousSibling->previousSibling;
        }

        $node = Node::convertNodesToNode(
            func_get_args(),
            $this->mOwnerDocument
        );
        $viablePreviousSibling = $viablePreviousSibling ?
            $viablePreviousSibling->nextSibling : $parent->firstChild;
        $this->mParentNode->preinsertNode($node, $viablePreviousSibling);
    }

    /**
     * Removes this ChildNode from its ParentNode.
     *
     * @link https://dom.spec.whatwg.org/#dom-childnode-remove
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
     * @link https://dom.spec.whatwg.org/#dom-childnode-replacewith
     *
     * @param Node|string ...$aNodes A set of Node objects or strings to be
     *     inserted in place of this ChildNode.
     */
    public function replaceWith()
    {
        $parent = $this->mParentNode;
        $nodes = func_get_args();

        if (!$parent) {
            return;
        }

        $viableNextSibling = $this->mNextSibling;

        while ($viableNextSibling) {
            if (!in_array($viableNextSibling, $nodes)) {
                break;
            }

            $viableNextSibling = $viableNextSibling->nextSibling;
        }

        $node = Node::convertNodesToNode($nodes, $this->mOwnerDocument);

        if ($this->mParentNode === $parent) {
            $parent->replaceNode($node, $this);
        } else {
            $parent->preinsertNode($node, $viableNextSibling);
        }
    }
}
