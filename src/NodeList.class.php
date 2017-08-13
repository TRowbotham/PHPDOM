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

    public function getIterator()
    {
        return new ArrayIterator($this->nodes->values());
    }
}
