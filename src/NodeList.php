<?php
namespace Rowbot\DOM;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Rowbot\DOM\Support\Collection\NodeSet;

/**
 * @see https://dom.spec.whatwg.org/#interface-nodelist
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList
 */
class NodeList implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var \Rowbot\DOM\Support\Collection\NodeSet
     */
    private $nodes;

    /**
     * Constructor.
     *
     * @param \Rowbot\DOM\Support\Collection\NodeSet $nodes
     *
     * @return void
     */
    public function __construct(NodeSet $nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'length':
                return $this->nodes->count();
        }
    }

    /**
     * Returns the node at the given index.
     *
     * @param int $index
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function item(int $index): ?Node
    {
        return $this->nodes->offsetGet($index);
    }

    /**
     * Indicates whether a node at the given offset exists.
     *
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->nodes->offsetExists($offset);
    }

    /**
     * Gets the node at the given offset.
     *
     * @param int $offset
     *
     * @return \Rowbot\DOM\Node|null
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
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->nodes->offsetSet($offset, $value);
    }

    /**
     * Noop
     *
     * @param int $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->nodes->offsetUnset($offset);
    }

    /**
     * Returns the number of nodes in the list.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->nodes->count();
    }

    /**
     * Returns the iterator object for the list.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->nodes->all());
    }
}
