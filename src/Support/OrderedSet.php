<?php
namespace Rowbot\DOM\Support;

use ArrayAccess;
use Countable;
use Iterator;

class OrderedSet implements ArrayAccess, Countable, Iterator
{
    protected $keys;
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
            return $this;
        }

        $this->map[$hash] = $item;
        $this->keys[] = $hash;
        $this->length++;

        return $this;
    }

    public function prepend($item)
    {
        $hash = $this->hash($item);

        if (isset($this->map[$hash])) {
            return $this;
        }

        array_unshift($this->keys, $hash);
        $this->map = [$hash => $item] + $this->map;
        $this->length++;

        return $this;
    }

    public function replace($item, $newItem)
    {
        $oldHash = $this->hash($item);

        if (!isset($this->map[$oldHash])) {
            return $this;
        }

        $newHash = $this->hash($newItem);
        $flipped = array_flip($this->keys);
        $containsNewItem = isset($this->map[$newHash]);

        if ($containsNewItem) {
            unset($this->map[$newHash]);
            $this->length--;
        }

        $offset = $flipped[$oldHash];
        $this->keys[$offset] = $newHash;
        $this->map = array_slice($this->map, 0, $offset, true)
            + [$newHash => $newItem]
            + array_slice($this->map, $offset + 1, null, true);

        if ($containsNewItem) {
            array_splice($this->keys, $flipped[$newHash], 1);
        }

        return $this;
    }

    public function remove($item)
    {
        $hash = $this->hash($item);

        if (!isset($this->map[$hash])) {
            return $this;
        }

        $this->length--;

        if ($this->keys[$this->length] === $hash) {
            array_pop($this->map);
            array_pop($this->keys);
            return $this;
        }

        unset($this->map[$hash]);
        $this->keys = array_keys($this->map);

        return $this;
    }

    public function contains($item)
    {
        return isset($this->map[$this->hash($item)]);
    }

    public function insertBefore($item, $newItem)
    {
        // If the item to be inserted before is null, append $newItem to the
        // list.
        if ($item === null) {
            return $this->append($newItem);
        }

        $hash = $this->hash($item);

        if (!isset($this->map[$hash])) {
            return $this;
        }

        $newHash = $this->hash($newItem);

        if (isset($this->map[$newHash])) {
            return $this;
        }

        if ($this->keys[0] === $hash) {
            array_unshift($this->keys, $newHash);
            $this->map = [$newHash => $newItem] + $this->map;
            $this->length++;
            return $this;
        }

        $offset = array_flip($this->keys)[$hash];
        $this->map = array_slice($this->map, 0, $offset, true)
            + [$newHash => $newItem]
            + array_slice($this->map, $offset - 1, null, true);
        array_splice($this->keys, $offset, 0, $newHash);
        $this->length++;

        return $this;
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
        if (isset($this->keys[$offset])
            && isset($this->map[$this->keys[$offset]])
        ) {
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

    public function prev()
    {
        if (isset($this->keys[--$this->position])) {
            return $this->map[$this->keys[$this->position]];
        }

        return null;
    }

    public function next()
    {
        if (isset($this->keys[++$this->position])) {
            return $this->map[$this->keys[$this->position]];
        }

        return null;
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

    public function clear()
    {
        $this->keys = [];
        $this->map = [];
        $this->length = 0;

        return $this;
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

    public function indexOf($item)
    {
        $hash = $this->hash($item);

        if (!isset($this->map[$hash])) {
            return -1;
        }

        return array_flip($this->keys)[$hash];
    }

    /**
     * Gets the first item in the list, if any.
     *
     * @param callable|null $callback
     *
     * @return mixed|null
     */
    public function first(callable $callback = null)
    {
        if (empty($this->keys)) {
            return null;
        }

        if ($callback === null) {
            return $this->map[$this->keys[0]];
        }

        foreach ($this->keys as $key => $hash) {
            if (call_user_func($callback, $this->map[$hash], $key)) {
                return $this->map[$hash];
            }
        }

        return null;
    }

    /**
     * Gets the last item in the list, if any.
     *
     * @param callable|null $callback
     *
     * @return mixed|null
     */
    public function last(callable $callback = null)
    {
        if (empty($this->keys)) {
            return null;
        }

        if ($callback === null) {
            return $this->map[$this->keys[$this->length - 1]];
        }

        foreach (array_reverse($this->keys) as $key => $hash) {
            if (call_user_func($callback, $this->map[$hash], $key)) {
                return $this->map[$hash];
            }
        }

        return null;
    }

    /**
     * Filters items from the set using a callback. The first parameter of the
     * callback is the indexed position of the item, and the second parameter of
     * the callback is the item itself.
     *
     * @param  callable   $callback
     *
     * @return OrderedSet
     */
    public function filter(callable $callback)
    {
        $set = new static();

        foreach ($this->keys as $index => $hash) {
            if (call_user_func($callback, $this->map[$hash], $index)) {
                $set->append($this->map[$hash]);
            }
        }

        return $set;
    }

    /**
     * Seek to the numeric position occupied by the given item.
     *
     * @param  mixed      $item
     *
     * @return OrderedSet
     */
    public function seekTo($item)
    {
        $hash = $this->hash($item);

        if (!isset($this->map[$hash])) {
            return $this;
        }

        $this->position = array_flip($this->keys)[$hash];

        return $this;
    }
}
