<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException;
use Rowbot\DOM\Support\UniquelyIdentifiable;

use function array_pop;
use function array_search;
use function array_splice;

/**
 * @implements \ArrayAccess<int, \Rowbot\DOM\Support\UniquelyIdentifiable>
 * @implements \IteratorAggregate<int, \Rowbot\DOM\Support\UniquelyIdentifiable>
 */
abstract class ObjectStack implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array<string, true>
     */
    protected $cache;

    /**
     * @var list<\Rowbot\DOM\Support\UniquelyIdentifiable>
     */
    protected $collection;

    /**
     * @var int
     */
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
     * @throws \Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException
     * @throws \Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException
     */
    public function replace(UniquelyIdentifiable $newItem, UniquelyIdentifiable $oldItem): void
    {
        if ($newItem === $oldItem) {
            return;
        }

        $oldHash = $oldItem->uuid();

        if (!isset($this->cache[$oldHash])) {
            throw new NotInCollectionException();
        }

        $newHash = $newItem->uuid();

        if (isset($this->cache[$newHash])) {
            throw new DuplicateItemException();
        }

        $index = array_search($oldItem, $this->collection, true);
        $this->collection[$index] = $newItem;
        unset($this->cache[$oldHash]);
        $this->cache[$newHash] = true;
    }

    /**
     * Inserts an object before an existing object in the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException
     * @throws \Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException
     */
    public function insertBefore(
        UniquelyIdentifiable $newItem,
        ?UniquelyIdentifiable $oldItem = null
    ): void {
        if ($oldItem === null || $this->size === 0) {
            $this->push($newItem);

            return;
        }

        if ($oldItem === $this->collection[0]) {
            $this->insertBefore($newItem, null);

            return;
        }

        if (!isset($this->cache[$oldItem->uuid()])) {
            throw new NotInCollectionException();
        }

        $newHash = $newItem->uuid();

        if (isset($this->cache[$newHash])) {
            throw new DuplicateItemException();
        }

        $index = array_search($oldItem, $this->collection, true);
        array_splice($this->collection, $index, 0, [$newItem]);
        $this->cache[$newHash] = true;
        ++$this->size;
    }

    /**
     * Inserts an object after an existing object in the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException
     * @throws \Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException
     */
    public function insertAfter(UniquelyIdentifiable $newItem, UniquelyIdentifiable $oldItem): void
    {
        if ($this->collection[$this->size - 1] === $oldItem) {
            $this->push($newItem);

            return;
        }

        if (!isset($this->cache[$oldItem->uuid()])) {
            throw new NotInCollectionException();
        }

        $newHash = $newItem->uuid();

        if (isset($this->cache[$newHash])) {
            throw new DuplicateItemException();
        }

        $index = array_search($oldItem, $this->collection, true);
        array_splice($this->collection, $index + 1, 0, [$newItem]);
        $this->cache[$newHash] = true;
        ++$this->size;
    }

    /**
     * Removes the given object from the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException
     */
    public function remove(UniquelyIdentifiable $item): void
    {
        $hash = $item->uuid();

        if (!isset($this->cache[$hash])) {
            throw new NotInCollectionException();
        }

        unset($this->cache[$hash]);
        --$this->size;

        if ($this->collection[$this->size] === $item) {
            array_pop($this->collection);

            return;
        }

        $index = array_search($item, $this->collection, true);
        array_splice($this->collection, $index, 1);
    }

    /**
     * Empties the stack.
     */
    public function clear(): void
    {
        $this->collection = [];
        $this->cache = [];
        $this->size = 0;
    }

    /**
     * Returns true if the given object is in the stack, false otherwise.
     */
    public function contains(UniquelyIdentifiable $item): bool
    {
        return isset($this->cache[$item->uuid()]);
    }

    /**
     * Returns the number of objects in the stack.
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Returns true if the stack is empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Returns the numerical index of the given object, or -1 if the object
     * does not exist in the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException
     */
    public function indexOf(UniquelyIdentifiable $item): int
    {
        if (!isset($this->cache[$item->uuid()])) {
            throw new NotInCollectionException();

            return -1;
        }

        return array_search($item, $this->collection, true);
    }

    /**
     * Pushes the given object onto the end of the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException
     */
    public function push(UniquelyIdentifiable $item): void
    {
        $hash = $item->uuid();

        if (isset($this->cache[$hash])) {
            throw new DuplicateItemException();
        }

        $this->collection[] = $item;
        $this->cache[$hash] = true;
        ++$this->size;
    }

    /**
     * Pops an object off the end of the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\EmptyStackException
     */
    public function pop(): UniquelyIdentifiable
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        $item = array_pop($this->collection);
        unset($this->cache[$item->uuid()]);
        --$this->size;

        return $item;
    }

    /**
     * Returns the last object in the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\EmptyStackException
     */
    public function top(): UniquelyIdentifiable
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->collection[$this->size - 1];
    }

    /**
     * Returns the first object in the stack.
     *
     * @throws \Rowbot\DOM\Parser\Collection\Exception\EmptyStackException
     */
    public function bottom(): UniquelyIdentifiable
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->collection[0];
    }

    /**
     * Returns an iterator that iterates from the end of the stack to the
     * beginning of the stack.
     *
     * @return \Rowbot\DOM\Parser\Collection\ReverseArrayIterator<\Rowbot\DOM\Support\UniquelyIdentifiable>
     */
    public function getIterator(): ReverseArrayIterator
    {
        return new ReverseArrayIterator($this->collection);
    }

    /**
     * Returns true if the numerical index exists, false otherwise.
     *
     * @param  int $index
     */
    public function offsetExists($index): bool
    {
        return isset($this->collection[$index]);
    }

    /**
     * Returns the object at the given numerical index or null if the numerical
     * index is not valid.
     *
     * @param  int $index
     */
    public function offsetGet($index): ?UniquelyIdentifiable
    {
        return $this->collection[$index] ?? null;
    }

    /**
     * Noop.
     *
     * @param  int                                      $index
     * @param  \Rowbot\DOM\Support\UniquelyIdentifiable $value
     */
    public function offsetSet($index, $value): void
    {
        // Do nothing.
    }

    /**
     * Noop.
     *
     * @param  int                  $index
     */
    public function offsetUnset($index): void
    {
        // Do nothing.
    }
}
