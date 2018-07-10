<?php
namespace Rowbot\DOM;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @see https://dom.spec.whatwg.org/#interface-nodelist
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList
 */
class NodeList implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var \Rowbot\DOM\Support\OrderedSet
     */
    private $nodes;

    /**
     * Constructor.
     *
     * @param \Rowbot\DOM\Support\OrderedSet $nodes
     *
     * @return void
     */
    public function __construct($nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function __get($name)
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
    public function item($index)
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
    public function offsetExists($offset)
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
    public function offsetGet($offset)
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
    public function offsetSet($offset, $value)
    {
        return $this->nodes->offsetSet($offset, $value);
    }

    /**
     * Noop
     *
     * @param int $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->nodes->offsetUnset($offset);
    }

    /**
     * Returns the number of nodes in the list.
     *
     * @return int
     */
    public function count()
    {
        return $this->nodes->count();
    }

    /**
     * Returns the iterator object for the list.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->nodes->values());
    }
}
