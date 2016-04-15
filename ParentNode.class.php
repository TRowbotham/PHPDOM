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
    public function append() {
        $node = Node::convertNodesToNode(func_get_args());
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
        $node = Node::convertNodesToNode(func_get_args());
        $this->preinsertNode($node, $this->mFirstChild);
    }

    private function filterChildElements($aNode)
    {
        return $aNode->nodeType == Node::ELEMENT_NODE;
    }

    private function getChildren()
    {
        return array_values(
            array_filter(
                $this->mChildNodes,
                array($this, 'filterChildElements')
            )
        );
    }

    private function getFirstElementChild()
    {
        $node = $this->mFirstChild;

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->nextSibling;
        }

        return $node;
    }

    private function getLastElementChild()
    {
        $node = $this->mLastChild;

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->previousSibling;
        }

        return $node;
    }

    private function getChildElementCount()
    {
        return count($this->getChildren());
    }
}
