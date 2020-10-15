<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\Collection;

use ArrayAccess;
use Countable;
use Rowbot\DOM\Support\UniquelyIdentifiable;
use SeekableIterator;

use function count;
use function current;
use function end;
use function key;
use function prev;

class ReverseArrayIterator implements ArrayAccess, Countable, SeekableIterator
{
    /**
     * @var list<\Rowbot\DOM\Support\UniquelyIdentifiable>
     */
    private $array;

    /**
     * @param list<\Rowbot\DOM\Support\UniquelyIdentifiable> $array
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * @param int $index
     */
    public function offsetExists($index): bool
    {
        return isset($this->array[$index]);
    }

    /**
     * @param int $index
     */
    public function offsetGet($index): ?UniquelyIdentifiable
    {
        return $this->array[$index] ?? null;
    }

    /**
     * @param int                                      $index
     * @param \Rowbot\DOM\Support\UniquelyIdentifiable $value
     */
    public function offsetSet($index, $value): void
    {
        $this->array[$index] = $value;
    }

    /**
     * @param int $index
     */
    public function offsetUnset($index): void
    {
        unset($this->array[$index]);
    }

    public function count(): int
    {
        return count($this->array);
    }

    public function current(): UniquelyIdentifiable
    {
        return current($this->array);
    }

    public function key(): int
    {
        return key($this->array);
    }

    public function next(): void
    {
        prev($this->array);
    }

    public function rewind(): void
    {
        end($this->array);
    }

    public function valid(): bool
    {
        return key($this->array) !== null;
    }

    /**
     * @param int $index
     */
    public function seek($index): void
    {
        end($this->array);

        while (($key = key($this->array)) !== $index && $key !== null) {
            prev($this->array);
        }
    }
}
