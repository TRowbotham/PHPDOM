<?php

declare(strict_types=1);

namespace Rowbot\DOM\Range;

final class RangeBoundary
{
    public BoundaryPoint $start;

    public BoundaryPoint $end;

    public function __construct(BoundaryPoint $start, BoundaryPoint $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @see https://dom.spec.whatwg.org/#range-collapsed
     */
    public function isCollapsed(): bool
    {
        return $this->start->node === $this->end->node && $this->start->offset === $this->end->offset;
    }

    public function __clone(): void
    {
        $this->start = clone $this->start;
        $this->end = clone $this->end;
    }
}
