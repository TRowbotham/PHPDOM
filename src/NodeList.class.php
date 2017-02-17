<?php
namespace phpjs;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * @see https://dom.spec.whatwg.org/#interface-nodelist
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList
 */
class NodeList implements ArrayAccess, Countable, Iterator
{
    private $nodes;

    public function __construct($aNodes)
    {
        $this->nodes = $aNodes;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'length':
                return $this->nodes->count();
        }
    }

    public function item($aIndex)
    {
        return $this->nodes->offsetGet($aIndex);
    }

    public function offsetExists($aOffset)
    {
        return $this->nodes->offsetExists($aOffset);
    }

    public function offsetGet($aOffset)
    {
        return $this->nodes->offsetGet($aOffset);
    }

    public function offsetSet($aOffset, $aValue)
    {
        return $this->nodes->offsetSet($aOffset, $aValue);
    }

    public function offsetUnset($aOffset)
    {
        $this->nodes->offsetUnset($aOffset);
    }

    public function count()
    {
        return $this->nodes->count();
    }

    public function current()
    {
        return $this->nodes->current();
    }

    public function key()
    {
        return $this->nodes->key();
    }

    public function next()
    {
        $this->nodes->next();
    }

    public function rewind()
    {
        $this->nodes->rewind();
    }

    public function valid()
    {
        return $this->nodes->valid();
    }
}
