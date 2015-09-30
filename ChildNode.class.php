<?php
// https://developer.mozilla.org/en-US/docs/Web/API/ChildNode
// https://dom.spec.whatwg.org/#interface-childnode

trait ChildNode {
    /**
     * Inserts any number of Node objects or strings after this ChildNode.
     *
     * @link https://dom.spec.whatwg.org/#dom-childnode-after
     *
     * @param  Node|string ...$aNodes A set of Node objects or strings to be inserted.
     */
    public function after() {
        $parent = $this->mParentNode;
        $nodes = func_get_args();

        if (!$parent || !func_num_args()) {
            return;
        }

        $viableNextSibling = $this->mNextSibling;

        while ($viableNextSibling) {
            if (!in_array($viableNextSibling, $nodes)) {
                break;
            }

            $viableNextSibling = $viableNextSibling->nextSibling;
        }

        $node = $this->mutationMethodMacro($nodes);
        $parent->_preinsertNodeBeforeChild($node, $viableNextSibling);
    }

    /**
     * Inserts any number of Node objects or strings before this ChildNode.
     *
     * @link https://dom.spec.whatwg.org/#dom-childnode-before
     *
     * @param  Node|string ...$aNodes A set of Node objects or strings to be inserted.
     */
    public function before() {
        $parent = $this->mParentNode;
        $nodes = func_get_args();

        if (!$parent || !func_num_args()) {
            return;
        }

        $viablePreviousSibling = $this->mPreviousSibling;

        while ($viablePreviousSibling) {
            if (!in_array($viablePreviousSibling, $nodes)) {
                break;
            }

            $viablePreviousSibling = $viablePreviousSibling->previousSibling;
        }

        $node = $this->mutationMethodMacro(func_get_args());
        $viablePreviousSibling = $viablePreviousSibling ? $viablePreviousSibling->nextSibling : $parent->firstChild;
        $this->mParentNode->_preinsertNodeBeforeChild($node, $viablePreviousSibling);
    }

    /**
     * Removes this ChildNode from its ParentNode.
     */
    public function remove() {
        if (!$this->mParentNode) {
            return;
        }

        $this->mParentNode->_removeChild($this);
    }

    /**
     * Replaces this ChildNode with any number of Node or DOMString objects.
     * @param  Node|DOMString ...$aNodes A set of Node or DOMString objects to be inserted in place of this ChildNode.
     */
    public function replaceWith() {
        if (!$this->parentNode || !func_num_args()) {
            return;
        }

        $node = $this->mutationMethodMacro(func_get_args());
        $this->mParentNode->replaceChild($node, $this);
    }
}
