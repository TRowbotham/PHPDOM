<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Rowbot\DOM\Support\Collection\NodeSet;

/**
 * @see https://dom.spec.whatwg.org/#interface-nodelist
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList
 *
 * @implements \ArrayAccess<int, \Rowbot\DOM\Node>
 * @implements \IteratorAggregate<int, \Rowbot\DOM\Node>
 */
class NodeList implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var \Rowbot\DOM\Support\Collection\NodeSet<\Rowbot\DOM\Node>
     */
    private $nodes;

    /**
     * @param \Rowbot\DOM\Support\Collection\NodeSet<\Rowbot\DOM\Node> $nodes
     */
    public function __construct(NodeSet $nodes)
    {
        $this->nodes = $nodes;
    }

    public function __get(string $name): int
    {
        switch ($name) {
            case 'length':
                return $this->nodes->count();
        }
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
     * @return \ArrayIterator<int, \Rowbot\DOM\Node>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->nodes->all());
    }
}
