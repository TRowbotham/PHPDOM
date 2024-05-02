<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayIterator;
use IteratorAggregate;
use Rowbot\DOM\Support\Collection\NodeSet;

/**
 * @template T of \Rowbot\DOM\Node
 *
 * @implements \Rowbot\DOM\NodeList<T>
 * @implements \IteratorAggregate<int, T>
 */
final class LiveNodeList implements IteratorAggregate, NodeList
{
    public int $length {
        get => $this->nodes->count();
    }

    /**
     * @var \Rowbot\DOM\Support\Collection\NodeSet<T>
     */
    private NodeSet $nodes;

    /**
     * @param \Rowbot\DOM\Support\Collection\NodeSet<T> $nodes
     */
    public function __construct(NodeSet $nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * Returns the node at the given index.
     */
    public function item(int $index): ?Node
    {
        return $this->nodes->offsetGet($index);
    }

    /**
     * Indicates whether a node at the given offset exists.
     *
     * @param int $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->nodes->offsetExists($offset);
    }

    /**
     * Gets the node at the given offset.
     *
     * @param int $offset
     */
    public function offsetGet($offset): ?Node
    {
        return $this->nodes->offsetGet($offset);
    }

    /**
     * Noop
     *
     * @param int $offset
     * @param \Rowbot\DOM\Node $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->nodes->offsetSet($offset, $value);
    }

    /**
     * Noop
     *
     * @param int $offset
     */
    public function offsetUnset($offset): void
    {
        $this->nodes->offsetUnset($offset);
    }

    /**
     * Returns the number of nodes in the list.
     */
    public function count(): int
    {
        return $this->nodes->count();
    }

    /**
     * Returns the iterator object for the list.
     *
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->nodes->all());
    }
}
