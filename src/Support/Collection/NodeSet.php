<?php
declare(strict_types=1);

namespace Rowbot\DOM\Support\Collection;

use ArrayAccess;
use Closure;
use Countable;
use Iterator;
use Rowbot\DOM\Node;

use function array_pop;
use function array_search;
use function array_shift;
use function array_splice;
use function array_unshift;

/**
 * NodeSet is a collection of node objects that does not allow for duplicate items.
 */
final class NodeSet implements ArrayAccess, Countable, Iterator
{
    /**
     * @var array<\Rowbot\DOM\Node>
     */
    private $list;

    /**
     * @var array<string, bool>
     */
    private $cache;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $cursor;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->list = [];
        $this->cache = [];
        $this->length = 0;
        $this->cursor = 0;
    }

    /**
     * Appends an item to the set if it doesn't already exsist in the set.
     *
     * @param \Rowbot\DOM\Node $item
     *
     * @return void
     */
    public function append(Node $item): void
    {
        $id = $item->uuid();

        if (isset($this->cache[$id])) {
            return;
        }

        $this->list[] = $item;
        $this->cache[$id] = true;
        ++$this->length;
    }

    /**
     * Prepends an item to the set if it doesn't already exist in the set.
     *
     * @param \Rowbot\DOM\Node $item
     *
     * @return void
     */
    public function prepend(Node $item): void
    {
        $id = $item->uuid();

        if (isset($this->cache[$id])) {
            return;
        }

        array_unshift($this->list, $item);
        $this->cache[$id] = true;
        ++$this->length;
    }

    /**
     * Replaces the first occurance of either item or the replacement item in
     * the set if they exist and removes all other occurances.
     *
     * @param \Rowbot\DOM\Node $item
     * @param \Rowbot\DOM\Node $newItem
     *
     * @return void
     */
    public function replace(Node $item, Node $newItem): void
    {
        // If for whatever reason item is being replaced by itself, just don't
        // bother doing any work.
        if ($item === $newItem) {
            return;
        }

        $itemId = $item->uuid();
        $newItemId = $newItem->uuid();
        $containsItem = isset($this->cache[$itemId]);
        $containsNewItem = isset($this->cache[$newItemId]);

        // If neither item nor its replacement item are contained in the list,
        // then return.
        if (!$containsItem && !$containsNewItem) {
            return;
        }

        // At this point, we know that the list contains the replacement item.
        // If it doesn't contain item, then there is nothing to replace or move.
        if (!$containsItem) {
            return;
        }

        // We now know that the list contains item and it will ultimately be
        // removed, so remove it from the cache.
        unset($this->cache[$itemId]);

        if ($containsNewItem) {
            // Since item is going to be removed, decrement the length.
            --$this->length;

            // If item is the last item in the list just pop it off as we don't
            // need to do anything with replacement item since it's already in
            // the list and came before item.
            if ($this->list[$this->length] === $item) {
                array_pop($this->list);
                return;
            }

            $popped = null;

            // If the replacement item is the last item in the list, pop it off
            // since we will be moving it to replace item later.
            if ($this->list[$this->length] === $newItem) {
                $popped = array_pop($this->list);
            }

            // Find item's position in the list.
            $itemIndex = array_search($item, $this->list, true);

            // If the replacement item wasn't the last item in the list, we need
            // to find its position in the list.
            if ($popped === null) {
                $newItemIndex = array_search($newItem, $this->list, true);

                // If item comes after the replacement item in the list, we only
                // need to remove item here since the replacement item is the
                // first instance of the two in this case.
                if ($itemIndex > $newItemIndex) {
                    array_splice($this->list, $itemIndex, 1);
                    return;
                }

                // At this point, we know that the replacement item comes before
                // item in the list. If the replacement item's index is 0, use
                // array_shift since its faster than splicing, otherwise, splice
                // it out of the list.
                if ($newItemIndex === 0) {
                    array_shift($this->list);
                } else {
                    array_splice($this->list, $newItemIndex, 1);
                }
            }

            // We removed the existing instance of replacement item from the
            // list above and now we will replace the item with the replacement
            // item.
            array_splice($this->list, $itemIndex, 1, [$newItem]);

            return;
        }

        // At this point the list only contains item and not the replacement
        // item. Add the replacement item to the cache as it is not currently in
        // the list.
        $this->cache[$newItemId] = true;

        // If item is the last item in the list, pop it off the list and append
        // the replacement item.
        if ($this->list[$this->length - 1] === $item) {
            array_pop($this->list);
            $this->list[] = $newItem;
            return;
        }

        $index = array_search($item, $this->list, true);
        array_splice($this->list, $index, 1, [$newItem]);
    }

    /**
     * Inserts an item before another item in the set.
     *
     * @param \Rowbot\DOM\Node $item
     * @param \Rowbot\DOM\Node $newItem
     *
     * @return void
     */
    public function insertBefore(Node $item, Node $newItem): void
    {
        if (!isset($this->cache[$item->uuid()])) {
            return;
        }

        $this->cache[$newItem->uuid()] = true;
        ++$this->length;

        // If we are trying to insert before the first item in the array use
        // array_unshift instead of searching the array and then splicing the
        // value in as unshifting is faster.
        if ($this->list[0] === $item) {
            array_unshift($this->list, $newItem);
            return;
        }

        $index = array_search($item, $this->list, true);
        array_splice($this->list, $index, 0, [$newItem]);
    }

    /**
     * Removes the given item from the set.
     *
     * @param \Rowbot\DOM\Node $item
     *
     * @return void
     */
    public function remove(Node $item): void
    {
        $id = $item->uuid();

        if (!isset($this->cache[$id])) {
            return;
        }

        // Remove the item from the cache.
        unset($this->cache[$id]);

        // If the given item is the last item in the array simply pop it off,
        // rather than searching the array and splicing the value out.
        if ($this->list[--$this->length] === $item) {
            array_pop($this->list);
            return;
        }

        // If item is the first item in the list shift it off the array as it
        // is faster than searching the array and then splicing out the value.
        if ($this->list[0] === $item) {
            array_shift($this->list);
            return;
        }

        $index = array_search($item, $this->list, true);
        array_splice($this->list, $index, 1);
    }

    /**
     * Empties the set.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->list = [];
        $this->cache = [];
        $this->length = 0;
    }

    /**
     * Determines if the set is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Determines if the set contains the given item.
     *
     * @param \Rowbot\DOM\Node $item
     *
     * @return bool
     */
    public function contains(Node $item): bool
    {
        return isset($this->cache[$item->uuid()]);
    }

    /**
     * Returns the number of items in the set.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length;
    }

    /**
     * Returns the position of the given item in the set.
     *
     * @param \Rowbot\DOM\Node $item
     *
     * @return int
     */
    public function indexOf(Node $item): int
    {
        if (!isset($this->cache[$item->uuid()])) {
            return -1;
        }

        return array_search($item, $this->list, true);
    }

    /**
     * Filters the set.
     *
     * @param \Closure $callback
     *
     * @return self
     */
    public function filter(Closure $callback): self
    {
        $list = new self();

        for ($i = 0; $i < $this->length; ++$i) {
            if ($callback($this->list[$i])) {
                $list->append($this->list[$i]);
            }
        }

        return $list;
    }

    /**
     * Returns the first item in the set.
     *
     * @param \Closure|null $callback (optional)
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function first(Closure $callback = null): ?Node
    {
        if ($callback === null) {
            return $this->list[0] ?? null;
        }

        for ($i = 0; $i < $this->length; ++$i) {
            if ($callback($this->list[$i])) {
                return $this->list[$i];
            }
        }

        return null;
    }

    /**
     * Returns the last item in the set.
     *
     * @param \Closure|null $callback (optional)
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function last(Closure $callback = null): ?Node
    {
        if ($callback === null) {
            return $this->list[$this->length - 1] ?? null;
        }

        for ($i = $this->length - 1; $i >= 0; --$i) {
            if ($callback($this->list[$i])) {
                return $this->list[$i];
            }
        }

        return null;
    }

    /**
     * Returns the entire set as an array.
     *
     * @return array<\Rowbot\DOM\Node>
     */
    public function all(): array
    {
        return $this->list;
    }

    /**
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->list[$offset]);
    }

    /**
     * @param int $offset
     *
     * @return \Rowbot\DOM\Node|null
     */
    public function offsetGet($offset): ?Node
    {
        return $this->list[$offset] ?? null;
    }

    /**
     * Noop.
     *
     * @param int              $offset
     * @param \Rowbot\DOM\Node $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * Noop.
     *
     * @param int $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
    }

    /**
     * @return \Rowbot\DOM\Node
     */
    public function current(): Node
    {
        return $this->list[$this->cursor];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->cursor;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        ++$this->cursor;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->cursor = 0;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->list[$this->cursor]);
    }
}
