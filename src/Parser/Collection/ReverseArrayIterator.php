<?php
namespace Rowbot\DOM\Parser\Collection;

use ArrayAccess;
use Countable;
use SeekableIterator;

class ReverseArrayIterator implements ArrayAccess, Countable, SeekableIterator
{
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function offsetExists($index)
    {
        return isset($this->array[$index]);
    }

    public function offsetGet($index)
    {
        return $this->array[$index] ?? null;
    }

    public function offsetSet($index, $value)
    {
        $this->array[$index] = $value;
    }

    public function offsetUnset($index)
    {
        unset($this->array[$index]);
    }

    public function count()
    {
        return count($this->array);
    }

    public function current()
    {
        return current($this->array);
    }

    public function key()
    {
        return key($this->array);
    }

    public function next()
    {
        prev($this->array);
    }

    public function rewind()
    {
        end($this->array);
    }

    public function valid()
    {
        return key($this->array) !== null;
    }

    public function seek($index)
    {
        end($this->array);

        while (($key = key($this->array)) !== $index && $key !== null) {
            prev($this->array);
        }
    }
}
