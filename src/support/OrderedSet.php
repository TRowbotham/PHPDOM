<?php
namespace phpjs\support;

use ArrayAccess;
use Countable;
use Iterator;

class OrderedSet implements ArrayAccess, Countable, Iterator
{
    protected $list;
    protected $length;
    protected $map;
    protected $position;

    public function __construct()
    {
        $this->keys = [];
        $this->length = 0;
        $this->map = [];
        $this->position = 0;
    }

    public function append($item)
    {
        $hash = $this->hash($item);

        if (isset($this->map[$hash])) {
            return;
        }

        $this->map[$hash] = $item;
        $this->keys[] = $hash;
        $this->length++;
    }

    public function prepend($item)
    {
        $hash = $this->hash($item);

        if (isset($this->map[$hash])) {
            return;
        }

        array_unshift($this->keys, $hash);
        $this->map = [$hash => $item] + $this->map;
        $this->length++;
    }

    public function replace($item, $newItem)
    {
        $oldHash = $this->hash($item);

        if (!isset($this->map[$oldHash])) {
            return;
        }

        $newHash = $this->hash($newItem);
        $flipped = array_flip($this->keys);
        $containsNewItem = isset($this->map[$newHash]);

        if ($containsNewItem) {
            unset($this->map[$newHash]);
            $this->length--;
        }

        $offset = $flipped[$oldHash];
        $this->mList[$offset] = $newHash;
        $this->map = array_slice($this->map, 0, $offset, true)
            + [$newHash => $newItem]
            + array_slice($this->map, $offset + 1, null, true);

        if ($containsNewItem) {
            array_splice($this->keys, $flipped[$newHash], 1);
        }
    }

    public function remove($item)
    {
        $hash = $this->hash($item);

        if (!isset($this->map[$hash])) {
            return;
        }

        $this->length--;

        if ($this->keys[$this->length] === $hash) {
            array_pop($this->map);
            array_pop($this->keys);
            return;
        }

        unset($this->map[$hash]);
        $this->keys = array_keys($this->map);
    }

    public function contains($item)
    {
        return isset($this->map[$this->hash($item)]);
    }

    public function count()
    {
        return $this->length;
    }

    public function isEmpty()
    {
        return $this->length == 0;
    }

    public function offsetExists($offset)
    {
        return isset($this->keys[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->map[$this->keys[$offset]])) {
            return $this->map[$this->keys[$offset]];
        }

        return null;
    }

    public function offsetSet($offset, $item)
    {
    }

    public function offsetUnset($offset)
    {
    }

    public function current()
    {
        return $this->map[$this->keys[$this->position]];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return isset($this->keys[$this->position]);
    }

    public function values()
    {
        return array_values($this->map);
    }

    public function empty()
    {
        $this->keys = [];
        $this->map = [];
        $this->length = 0;
    }

    public function get($item)
    {
        $hash = $this->hash($item);

        if (isset($this->map[$hash])) {
            return $this->map[$hash];
        }

        return null;
    }

    public function hash($item)
    {
        if (is_string($item)) {
            return md5($item);
        }

        if (is_object($item)) {
            return spl_object_hash($item);
        }
    }
}
