<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-parentnode
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ParentNode
 *
 * @property-read list<\Rowbot\DOM\Element> $children
 * @property-read \Rowbot\DOM\Element|null  $firstElementChild
 * @property-read \Rowbot\DOM\Element|null  $lastElementChild
 * @property-read int                       $childElementCount
 */
interface ParentNode
{
    /**
     * Inserts nodes before the first child of this node, while replacing strings in nodes with
     * equivalent Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-prepend
     *
     * @param Node|string $nodes One or more Nodes or strings to be prepended to this node
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function prepend(...$nodes): void;

    /**
     * Inserts nodes after the last child of this node, while replacing strings in nodes with
     * equvilant Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-append
     *
     * @param \Rowbot\DOM\Node|string $nodes One or more Nodes or strings to be appended to this
     *                                       Node.
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function append(...$nodes): void;

    /**
     * Replaces all children of node with nodes, while replacing strings in nodes with equivilant
     * Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-replacechildren
     *
     * @param \Rowbot\DOM\Node|string $nodes One or more Nodes or strings to replace
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function replaceChildren(...$nodes): void;
}
