<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Range\BoundaryPoint;
use Rowbot\DOM\Range\RangeBoundary;

/**
 * @see https://dom.spec.whatwg.org/#abstractrange
 */
abstract class AbstractRange
{
    public Node $startContainer {
        get => $this->range->start->node;
    }

    public int $startOffset {
        get => $this->range->start->offset;
    }

    public Node $endContainer {
        get => $this->range->end->node;
    }

    public int $endOffset {
        get => $this->range->end->offset;
    }

    public bool $collapsed {
        get => $this->range->isCollapsed();
    }

    protected RangeBoundary $range;

    public function __construct(BoundaryPoint $start, BoundaryPoint $end)
    {
        $this->range = new RangeBoundary($start, $end);
    }

    public function __clone(): void
    {
        $this->range = clone $this->range;
    }
}
