<?php
namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

/**
 * @see https://dom.spec.whatwg.org/#interface-parentnode
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ParentNode
 */
trait ParentNode
{
    use ChildOrParentNode;

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
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);
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
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);
        $this->preinsertNode($node, $this->childNodes->first());
    }

    protected function getChildren()
    {
        return $this->childNodes->filter(function ($node) {
            return $node->nodeType == Node::ELEMENT_NODE;
        })->values();
    }

    protected function getFirstElementChild()
    {
        $node = $this->childNodes->first();

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
        $node = $this->childNodes->last();

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
        return \count($this->getChildren());
    }
}
