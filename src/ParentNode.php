<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

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
     * @see https://dom.spec.whatwg.org/#dom-parentnode-append
     *
     * @param Node|string ...$nodes One or more Nodes or strings to be
     *     appended to this Node.
     */
    public function append(...$nodes)
    {
        $node = Node::convertNodesToNode($nodes, $this->nodeDocument);
        $this->preinsertNode($node, null);
    }

    /**
     * Inserts nodes before the first child of this node, while replacing
     * strings in nodes with equivalent Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-prepend
     *
     * @param Node|string ...$nodes One or more Nodes or strings to be
     *     prepended to this node;
     */
    public function prepend(...$nodes)
    {
        $node = Node::convertNodesToNode($nodes, $this->nodeDocument);
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
