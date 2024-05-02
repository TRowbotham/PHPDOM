<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use Countable;
use Traversable;

/**
 * @see https://dom.spec.whatwg.org/#interface-nodelist
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList
 *
 * @template T of \Rowbot\DOM\Node
 *
 * @extends \ArrayAccess<int, T>
 * @extends \Traversable<int, T>
 */
interface NodeList extends ArrayAccess, Countable, Traversable
{
    public int $length { get; }

    /**
     * Returns the node at the given index.
     */
    public function item(int $index): ?Node;

    /**
     * Indicates whether a node at the given offset exists.
     *
     * @param int $offset
     */
    public function offsetExists($offset): bool;

    /**
     * Gets the node at the given offset.
     *
     * @param int $offset
     */
    public function offsetGet($offset): ?Node;

    /**
     * Noop
     *
     * @param int $offset
     * @param \Rowbot\DOM\Node $value
     */
    public function offsetSet($offset, $value): void;

    /**
     * Noop
     *
     * @param int $offset
     */
    public function offsetUnset($offset): void;
}
