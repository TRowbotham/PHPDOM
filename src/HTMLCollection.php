<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use ArrayAccess;
use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\TypeError;

use function is_int;
use function is_string;
use function iterator_count;

/**
 * @see https://dom.spec.whatwg.org/#interface-htmlcollection
 *
 * @template TValue of \Rowbot\DOM\Element\HTML\HTMLElement
 *
 * @implements \ArrayAccess<int|string, TValue>
 * @implements \IteratorAggregate<int, TValue>
 *
 * @property-read int $length
 */
class HTMLCollection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var \Closure(\Rowbot\DOM\Node): \Generator<int, TValue>
     */
    private $filter;

    /**
     * @var \Rowbot\DOM\Node
     */
    private $root;

    public function __construct(Node $root, Closure $filter)
    {
        $this->filter = $filter;
        $this->root = $root;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'length':
                return iterator_count(($this->filter)($this->root));
        }
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-htmlcollection-item
     *
     * @return TValue|null
     */
    public function item(int $index): ?Element
    {
        $index = Utils::unsignedLong($index);

        // must return the indexth element in the collection. If there is no indexth element in the
        // collection, then the method must return null.
        foreach (($this->filter)($this->root) as $j => $item) {
            if ($index === $j) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @see https://dom.spec.whatwg.org/#dom-htmlcollection-nameditem-key
     *
     * @return TValue|null
     */
    public function namedItem(string $name): ?Element
    {
        // 1. If key is the empty string, return null.
        if ($name === '') {
            return null;
        }

        // 2. Return the first element in the collection for which at least one of the following is
        // true:
        foreach (($this->filter)($this->root) as $item) {
            // - it has an ID which is key;
            // - it is in the HTML namespace and has a name attribute whose value is key;
            if (
                $item->id === $name
                || (
                    $item->namespaceURI === Namespaces::HTML
                    && $item->getAttributeNS(null, $name) === $name
                )
            ) {
                return $item;
            }
        }

        // or null if there is no such element.
        return null;
    }

    /**
     * @param int|string $offset
     */
    public function offsetExists($offset): bool
    {
        if (is_int($offset)) {
            $offset = Utils::unsignedLong($offset);

            return $offset < iterator_count(($this->filter)($this->root));
        } elseif (is_string($offset)) {
            if ($offset === '') {
                return false;
            }

            foreach (($this->filter)($this->root) as $item) {
                if (
                    $item->id === $offset
                    || (
                        $item->namespaceURI === Namespaces::HTML
                        && $item->getAttributeNS(null, $offset) === $offset
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param int|string $offset
     *
     * @return TValue|null
     */
    public function offsetGet($offset)
    {
        if (is_int($offset)) {
            $offset = Utils::unsignedLong($offset);

            foreach (($this->filter)($this->root) as $j => $item) {
                if ($offset === $j) {
                    return $item;
                }
            }
        } elseif (is_string($offset)) {
            if ($offset === '') {
                return null;
            }

            foreach (($this->filter)($this->root) as $item) {
                if (
                    $item->id === $offset
                    || (
                        $item->namespaceURI === Namespaces::HTML
                        && $item->getAttributeNS(null, $offset) === $offset
                    )
                ) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @param int|string $offset
     * @param TValue     $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new TypeError();
    }

    /**
     * @param int|string $offset
     */
    public function offsetUnset($offset): void
    {
        // Do nothing
    }

    public function count(): int
    {
        return iterator_count(($this->filter)($this->root));
    }

    /**
     * @return \Generator<int, TValue>
     */
    public function getIterator(): Generator
    {
        return ($this->filter)($this->root);
    }
}
