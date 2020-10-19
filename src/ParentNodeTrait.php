<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Generator;
use Rowbot\DOM\Element\Element;

/**
 * This trait is meant to be used to fullfill the requirements of the ParentNode interface in the
 * context of a Node object.
 */
trait ParentNodeTrait
{
    use ChildOrParentNode;

    /**
     * @var \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element>|null
     */
    private $childElements;

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
     * @return \Rowbot\DOM\HTMLCollection<\Rowbot\DOM\Element\Element>
     */
    protected function getChildren(): HTMLCollection
    {
        if ($this->childElements !== null) {
            return $this->childElements;
        }

        $this->childElements = new HTMLCollection($this, static function (self $root): Generator {
            $node = $root->firstChild;

            while ($node !== null) {
                if ($node instanceof Element) {
                    yield $node;
                }

                $node = $node->nextSibling;
            }
        });

        return $this->childElements;
    }

    /**
     * Gets the first element child.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-firstelementchild
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
     */
    protected function getChildElementCount(): int
    {
        return $this->getChildren()->count();
    }
}
