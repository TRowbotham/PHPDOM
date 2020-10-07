<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

use function count;

/**
 * This trait is meant to be used to fullfill the requirements of the ParentNode interface in the
 * context of a Node object.
 */
trait ParentNodeTrait
{
    use ChildOrParentNode;

    /**
     * @see https://dom.spec.whatwg.org/#dom-parentnode-append
     */
    public function append(...$nodes): void
    {
        // 1. Let node be the result of converting nodes into a node given nodes and this’s node
        // document.
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        // 2. Append node to this.
        $this->preinsertNode($node, null);
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-parentnode-prepend
     */
    public function prepend(...$nodes): void
    {
        // 1. Let node be the result of converting nodes into a node given nodes and this’s node
        // document.
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        // 2. Pre-insert node into this before this’s first child.
        $this->preinsertNode($node, $this->childNodes->first());
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-parentnode-replacechildren
     */
    public function replaceChildren(...$nodes): void
    {
        // 1. Let node be the result of converting nodes into a node given nodes and this’s node
        // document.
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        // 2. Ensure pre-insertion validity of node into this before null.
        $this->ensurePreinsertionValidity($node, null);

        // 3. Replace all with node within this.
        $this->replaceAllNodes($node);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-children
     *
     * @return \Rowbot\DOM\Element\Element[]
     */
    protected function getChildren(): array
    {
        return $this->childNodes->filter(function ($node) {
            return $node->nodeType == Node::ELEMENT_NODE;
        })->all();
    }

    /**
     * Gets the first element child.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-firstelementchild
     *
     * @return \Rowbot\DOM\Element\Element|null
     */
    protected function getFirstElementChild(): ?Element
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

    /**
     * Gets the last element child.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-lastelementchild
     *
     * @return \Rowbot\DOM\Element\Element|null
     */
    protected function getLastElementChild(): ?Element
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

    /**
     * Gets the number of element children.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-childelementcount
     *
     * @return int
     */
    protected function getChildElementCount(): int
    {
        return count($this->getChildren());
    }
}
