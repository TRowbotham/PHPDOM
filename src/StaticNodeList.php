<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayIterator;
use IteratorAggregate;

use function count;

/**
 * @template T of \Rowbot\DOM\Node
 *
 * @implements \Rowbot\DOM\NodeList<T>
 * @implements \IteratorAggregate<int, T>
 */
final class StaticNodeList implements IteratorAggregate, NodeList
{
    public int $length {
        get => count($this->nodes);
    }

    /**
     * @var list<T>
     */
    private array $nodes;

    /**
     * @param list<T> $nodes
     */
    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * Returns the node at the given index.
     */
    public function item(int $index): ?Node
    {
        return $this->nodes[$index] ?? null;
    }

    /**
     * Indicates whether a node at the given offset exists.
     *
     * @param int $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->nodes[$offset]);
    }

    /**
     * Gets the node at the given offset.
     *
     * @param int $offset
     */
    public function offsetGet($offset): ?Node
    {
        return $this->nodes[$offset] ?? null;
    }

    /**
     * Noop
     *
     * @param int $offset
     * @param \Rowbot\DOM\Node $value
     */
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * Noop
     *
     * @param int $offset
     */
    public function offsetUnset($offset): void
    {
    }

    /**
     * Returns the number of nodes in the list.
     */
    public function count(): int
    {
        return count($this->nodes);
    }

    /**
     * Returns the iterator object for the list.
     *
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->nodes);
    }
}
