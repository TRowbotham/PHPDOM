<?php

declare(strict_types=1);

namespace Rowbot\DOM\Support\Collection;

use Generator;
use IteratorAggregate;
use WeakReference;

/**
 * @template TValue of object
 *
 * @implements \IteratorAggregate<int, TValue>
 */
class WeakCollection implements IteratorAggregate
{
    /**
     * @var array<int, \WeakReference<TValue>>
     */
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    /**
     * @param TValue $item
     */
    public function attach($item): void
    {
        $this->list[] = WeakReference::create($item);
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
        foreach ($this->list as $i => $weakRef) {
            $ref = $weakRef->get();

            if ($ref === null) {
                unset($this->list[$i]);

                continue;
            }

            yield $i => $ref;
        }
    }
}
