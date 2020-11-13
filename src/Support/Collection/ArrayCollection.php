<?php

declare(strict_types=1);

namespace Rowbot\DOM\Support\Collection;

use Generator;
use IteratorAggregate;

/**
 * @template TValue of object
 *
 * @implements \IteratorAggregate<int, TValue>
 */
class ArrayCollection implements IteratorAggregate
{
    /**
     * @var array<int, TValue>
     */
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function attach($item): void
    {
        $this->list[] = $item;
    }

    public function unset(int $i): void
    {
        unset($this->list[$i]);
    }

    /**
     * @return \Generator<int, TValue>
     */
    public function getIterator(): Generator
    {
        foreach ($this->list as $i => $item) {
            yield $i => $item;
        }
    }
}
