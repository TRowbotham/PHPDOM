<?php
namespace phpjs;

use phpjs\elements\Element;

/**
 * @see https://dom.spec.whatwg.org/#interface-parentnode
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ParentNode
 */
trait ParentNode
{
    /**
     * Inserts nodes after the last child of this node, while replacing strings
     * in nodes with equvilant Text nodes.
     *
     * @param Node|DOMString ...$aNodes One or more Nodes or strings to be
     *     appended to this Node.
     */
    public function append()
    {
        $owner = $this->mOwnerDocument ?: $this;
        $node = Node::convertNodesToNode(func_get_args(), $owner);
        $this->preinsertNode($node, null);
    }

    /**
     * Inserts nodes before the first child of this node, while replacing
     * strings in nodes with equivalent Text nodes.
     *
     * @param Node|DOMString ...$aNodes One or more Nodes or strings to be
     *     prepended to this node;
     */
    public function prepend()
    {
        $owner = $this->mOwnerDocument ?: $this;
        $node = Node::convertNodesToNode(func_get_args(), $owner);
        $this->preinsertNode($node, $this->mChildNodes->first());
    }

    protected function getChildren()
    {
        return $this->mChildNodes->filter(function ($index, $node) {
            return $node->nodeType == Node::ELEMENT_NODE;
        })->values();
    }

    protected function getFirstElementChild()
    {
        $node = $this->mChildNodes->first();

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->nextSibling;
        }

        return $node;
    }

    protected function getLastElementChild()
    {
        $node = $this->mChildNodes->last();

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->previousSibling;
        }

        return $node;
    }

    protected function getChildElementCount()
    {
        return count($this->getChildren());
    }
}
