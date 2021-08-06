<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use Countable;
use Generator;
use IteratorAggregate;
use Rowbot\DOM\Parser\Collection\Exception\DuplicateItemException;
use Rowbot\DOM\Parser\Collection\Exception\EmptyStackException;
use Rowbot\DOM\Parser\Collection\Exception\NotInCollectionException;

use function array_pop;
use function array_search;
use function array_splice;
use function spl_object_id;

/**
 * @template TValue of object
 *
 * @implements \IteratorAggregate<int, TValue>
 */
abstract class ObjectStack implements Countable, IteratorAggregate
{
    /**
     * @var array<int, true>
     */
    protected array $cache;

    /**
     * @var list<TValue>
     */
    protected array $stack;

    protected int $size;

    public function __construct()
    {
        $this->stack = [];
        $this->cache = [];
        $this->size = 0;
    }

    /**
     * @param TValue $item
     */
    public function contains($item): bool
    {
        return $this->cache[spl_object_id($item)] ?? false;
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
        $id = spl_object_id($item);

        if (isset($this->cache[$id])) {
            throw new DuplicateItemException();
        }

        $this->stack[] = $item;
        $this->cache[$id] = true;
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
        unset($this->cache[spl_object_id($popped)]);
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
        $this->cache = [];
        $this->size = 0;
    }

    /**
     * @param TValue $item
     */
    public function indexOf($item): int
    {
        if (!isset($this->cache[spl_object_id($item)])) {
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
        $id = spl_object_id($item);

        if (!isset($this->cache[$id])) {
            throw new NotInCollectionException();
        }

        unset($this->cache[$id]);
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
        $newItemId = spl_object_id($newItem);

        if (isset($this->cache[$newItemId])) {
            throw new DuplicateItemException();
        }

        $oldItemId = spl_object_id($oldItem);

        if (!isset($this->cache[$oldItemId])) {
            throw new NotInCollectionException();
        }

        $index = array_search($oldItem, $this->stack, true);
        $this->stack[$index] = $newItem;
        unset($this->cache[$oldItemId]);
        $this->cache[$newItemId] = true;
    }

    /**
     * @return \Generator<int, TValue>
     */
    public function getIterator(): Generator
    {
        $stack = $this->stack;
        $size = $this->size;

        for ($i = $size - 1; $i >= 0; --$i) {
            yield $i => $stack[$i];
        }
    }
}
