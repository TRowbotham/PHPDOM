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
    private $nodes;

    public function __construct($nodes)
    {
        $this->nodes = $nodes;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'length':
                return $this->nodes->count();
        }
    }

    public function item($index)
    {
        return $this->nodes->offsetGet($index);
    }

    public function offsetExists($offset)
    {
        return $this->nodes->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->nodes->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->nodes->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->nodes->offsetUnset($offset);
    }

    public function count()
    {
        return $this->nodes->count();
    }

    public function getIterator()
    {
        return new ArrayIterator($this->nodes->values());
    }
}
