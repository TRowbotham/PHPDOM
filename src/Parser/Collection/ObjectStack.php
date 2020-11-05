<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use Countable;
use Generator;
use IteratorAggregate;
use Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException;
use SplObjectStorage;

use function array_pop;
use function array_search;
use function array_splice;

/**
 * @template TValue of object
 *
 * @implements \IteratorAggregate<int, TValue>
 */
abstract class ObjectStack implements Countable, IteratorAggregate
{
    /**
     * @var \SplObjectStorage<TValue, null>
     */
    protected $cache;

    /**
     * @var list<TValue>
     */
    protected $stack;

    /**
     * @var int
     */
    protected $size;

    public function __construct()
    {
        $this->stack = [];
        $this->cache = new SplObjectStorage();
        $this->size = 0;
    }

    /**
     * @param TValue $item
     */
    public function contains($item): bool
    {
        return $this->cache->contains($item);
    }

    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    public function count(): int
    {
        return $this->size;
    }

    /**
     * @param TValue $item
     */
    public function push($item): void
    {
        if ($this->cache->contains($item)) {
            throw new DuplicateItemException();
        }

        $this->stack[] = $item;
        $this->cache->attach($item);
        ++$this->size;
    }

    /**
     * @return TValue
     */
    public function pop()
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        $popped = array_pop($this->stack);
        $this->cache->detach($popped);
        --$this->size;

        return $popped;
    }

    /**
     * @return TValue
     */
    public function top()
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->stack[$this->size - 1];
    }

    /**
     * @return TValue
     */
    public function bottom()
    {
        if ($this->size === 0) {
            throw new EmptyStackException();
        }

        return $this->stack[0];
    }

    public function clear(): void
    {
        $this->stack = [];
        $this->cache = new SplObjectStorage();
        $this->size = 0;
    }

    /**
     * @param TValue $item
     */
    public function indexOf($item): int
    {
        if (!$this->cache->contains($item)) {
            throw new NotInCollectionException();
        }

        return array_search($item, $this->stack, true);
    }

    /**
     * @return TValue
     */
    public function itemAt(int $index)
    {
        return $this->stack[$index];
    }

    /**
     * @param TValue $item
     */
    public function remove($item): void
    {
        if (!$this->cache->contains($item)) {
            throw new NotInCollectionException();
        }

        $this->cache->detach($item);
        --$this->size;

        if ($this->stack[$this->size] === $item) {
            array_pop($this->stack);

            return;
        }

        $index = array_search($item, $this->stack, true);
        array_splice($this->stack, $index, 1);
    }

    /**
     * @param TValue $newItem
     * @param TValue $oldItem
     */
    public function replace($newItem, $oldItem): void
    {
        if ($this->cache->contains($newItem)) {
            throw new DuplicateItemException();
        }

        if (!$this->cache->contains($oldItem)) {
            throw new NotInCollectionException();
        }

        $index = array_search($oldItem, $this->stack, true);
        $this->stack[$index] = $newItem;
        $this->cache->detach($oldItem);
        $this->cache->attach($newItem);
    }

    public function getIterator(): Generator
    {
        $stack = $this->stack;
        $size = $this->size;

        for ($i = $size - 1; $i >= 0; --$i) {
            yield $i => $stack[$i];
        }
    }

    public function __clone()
    {
        $this->cache = clone $this->cache;
    }
}
