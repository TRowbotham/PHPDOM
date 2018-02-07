<?php
namespace Rowbot\DOM\Parser\Collection;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException;
use Rowbot\DOM\Support\UniquelyIdentifiable;

abstract class ObjectStack implements ArrayAccess, Countable, IteratorAggregate
{
    protected $cache;
    protected $collection;
    protected $size;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cache = [];
        $this->collection = [];
        $this->size = 0;
    }

    /**
     * Replaces an object with a different object. Trying to replace an object
     * with the same object will do nothing.
     *
     * @param  UniquelyIdentifiable $newItem
     * @param  UniquelyIdentifiable $oldItem
     *
     * @throws NotInCollectionException
     * @throws DuplicateItemException
     *
     * @return void
     */
    public function replace(
        UniquelyIdentifiable $newItem,
        UniquelyIdentifiable $oldItem
    ) {
        if ($newItem === $oldItem) {
            return;
        }

        $oldHash = $oldItem->uuid()->toString();

        if (!isset($this->cache[$oldHash])) {
            throw new NotInCollectionException();
            return;
        }

        $newHash = $newItem->uuid()->toString();

        if (isset($this->cache[$newHash])) {
            throw new DuplicateItemException();
            return;
        }

        $index = \array_search($oldItem, $this->collection, true);
        $this->collection[$index] = $newItem;
        unset($this->cache[$oldHash]);
        $this->cache[$newHash] = true;
    }

    /**
     * Inserts an object before an existing object in the stack.
     *
     * @param  UniquelyIdentifiable  $newItem
     * @param  ?UniquelyIdentifiable $oldItem
     *
     * @throws NotInCollectionException
     * @throws DuplicateItemException
     *
     * @return void
     */
    public function insertBefore(
        UniquelyIdentifiable $newItem,
        UniquelyIdentifiable $oldItem = null
    ) {
        if ($oldItem === null || $this->size == 0) {
            $this->append($newItem);
            return;
        }

        if ($oldItem === $this->collection[0]) {
            $this->prepend($newItem);
            return;
        }

        if (!isset($this->cache[$oldItem->uuid()->toString()])) {
            throw new NotInCollectionException();
            return;
        }

        $newHash = $newItem->uuid()->toString();

        if (isset($this->cache[$newHash])) {
            throw new DuplicateItemException();
            return;
        }

        $index = \array_search($oldItem, $this->collection, true);
        \array_splice($this->collection, $index, 0, [$newItem]);
        $this->cache[$newHash] = true;
        ++$this->size;
    }

    /**
     * Inserts an object after an existing object in the stack.
     *
     * @param  UniquelyIdentifiable $newItem
     * @param  UniquelyIdentifiable $oldItem
     *
     * @throws NotInCollectionException
     * @throws DuplicateItemException
     *
     * @return void
     */
    public function insertAfter(
        UniquelyIdentifiable $newItem,
        UniquelyIdentifiable $oldItem
    ) {
        if ($this->collection[$this->size - 1] === $oldItem) {
            $this->append($newItem);
            return;
        }

        if (!isset($this->cache[$oldItem->uuid()->toString()])) {
            throw new NotInCollectionException();
            return;
        }

        $newHash = $newItem->uuid()->toString();

        if (isset($this->cache[$newHash])) {
            throw new DuplicateItemException();
            return;
        }

        $index = \array_search($oldItem, $this->collection, true);
        \array_splice($this->collection, $index + 1, 0, [$newItem]);
        $this->cache[$newHash] = true;
        ++$this->size;
    }

    /**
     * Removes the given object from the stack.
     *
     * @param  UniquelyIdentifiable $item
     *
     * @throws NotInCollectionException
     *
     * @return void
     */
    public function remove(UniquelyIdentifiable $item)
    {
        $hash = $item->uuid()->toString();

        if (!isset($this->cache[$hash])) {
            throw new NotInCollectionException();
            return;
        }

        unset($this->cache[$hash]);
        --$this->size;

        if ($this->collection[$this->size] === $item) {
            \array_pop($this->collection);
            return;
        }

        $index = \array_search($item, $this->collection, true);
        \array_splice($this->collection, $index, 1);
    }

    /**
     * Empties the stack.
     *
     * @return void
     */
    public function clear()
    {
        $this->collection = [];
        $this->cache = [];
        $this->size = 0;
    }

    /**
     * Returns true if the given object is in the stack, false otherwise.
     *
     * @param  UniquelyIdentifiable $item
     *
     * @return bool
     */
    public function contains(UniquelyIdentifiable $item): bool
    {
        return isset($this->cache[$item->uuid()->toString()]);
    }

    /**
     * Returns the number of objects in the stack.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Returns true if the stack is empty, false otherwise.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size == 0;
    }

    /**
     * Returns the numerical index of the given object, or -1 if the object
     * does not exist in the stack.
     *
     * @param  UniquelyIdentifiable $item
     *
     * @throws NotInCollectionException
     *
     * @return int
     */
    public function indexOf(UniquelyIdentifiable $item): int
    {
        if (!isset($this->cache[$item->uuid()->toString()])) {
            throw new NotInCollectionException();
            return -1;
        }

        return \array_search($item, $this->collection, true);
    }

    /**
     * Pushes the given object onto the end of the stack.
     *
     * @param  UniquelyIdentifiable $item
     *
     * @throws DuplicateItemException
     *
     * @return void
     */
    public function push(UniquelyIdentifiable $item)
    {
        $hash = $item->uuid()->toString();

        if (isset($this->cache[$hash])) {
            throw new DuplicateItemException();
            return;
        }

        $this->collection[] = $item;
        $this->cache[$hash] = true;
        ++$this->size;
    }

    /**
     * Pops an object off the end of the stack.
     *
     * @throws EmptyStackException
     *
     * @return ?UniquelyIdentifiable
     */
    public function pop()
    {
        if ($this->size == 0) {
            throw new EmptyStackException();
            return;
        }

        $item = \array_pop($this->collection);
        unset($this->cache[$item->uuid()->toString()]);
        --$this->size;
        return $item;
    }

    /**
     * Returns the last object in the stack.
     *
     * @throws EmptyStackException
     *
     * @return ?UniquelyIdentifiable
     */
    public function top()
    {
        if ($this->size == 0) {
            throw new EmptyStackException();
            return;
        }

        return $this->collection[$this->size - 1];
    }

    /**
     * Returns the first object in the stack.
     *
     * @throws EmptyStackException
     *
     * @return ?UniquelyIdentifiable
     */
    public function bottom()
    {
        if ($this->size == 0) {
            throw new EmptyStackException();
            return;
        }

        return $this->collection[0];
    }

    /**
     * Returns an iterator that iterates from the end of the stack to the
     * beginning of the stack.
     *
     * @return ReverseArrayIterator
     */
    public function getIterator()
    {
        return new ReverseArrayIterator($this->collection);
    }

    /**
     * Returns true if the numerical index exists, false otherwise.
     *
     * @param  int $index
     *
     * @return bool
     */
    public function offsetExists($index)
    {
        return isset($this->collection[$index]);
    }

    /**
     * Returns the object at the given numerical index or null if the numerical
     * index is not valid.
     *
     * @param  int $index
     *
     * @return ?UniquelyIdentifiable
     */
    public function offsetGet($index)
    {
        return $this->collection[$index] ?? null;
    }

    /**
     * Noop.
     *
     * @param  int                  $index
     * @param  UniquelyIdentifiable $value
     *
     * @return void
     */
    public function offsetSet($index, $value)
    {
    }

    /**
     * Noop.
     *
     * @param  int                  $index
     * @param  UniquelyIdentifiable $value
     *
     * @return void
     */
    public function offsetUnset($index)
    {
    }
}
