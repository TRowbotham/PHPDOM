<?php

declare(strict_types=1);

namespace Rowbot\DOM;

/**
 * @see https://dom.spec.whatwg.org/#interface-childnode
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ChildNode
 */
interface ChildNode
{
    /**
     * Inserts nodes just before node, while replacing strings in nodes with equivalent Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-before
     *
     * @param \Rowbot\DOM\Node|string $nodes A set of Node objects or strings to be inserted.
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function before(...$nodes): void;

    /**
     * Inserts nodes just after node, while replacing strings in nodes with equivalent Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-after
     *
     * @param \Rowbot\DOM\Node|string $nodes A set of Node objects or strings to be inserted.
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function after(...$nodes): void;

    /**
     * Replaces node with nodes, while replacing strings in nodes with equivalent Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-replacewith
     *
     * @param \Rowbot\DOM\Node|string $nodes A set of Node objects or strings to be inserted in
     *                                       place of this ChildNode.
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function replaceWith(...$nodes): void;

    /**
     * Removes this node from its parent node.
     *
     * @see https://dom.spec.whatwg.org/#dom-childnode-remove
     */
    public function remove(): void;
}
