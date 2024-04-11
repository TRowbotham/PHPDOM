<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Range\BoundaryPoint;
use Rowbot\DOM\Range\RangeBoundary;

/**
 * @see https://dom.spec.whatwg.org/#abstractrange
 *
 * @property-read \Rowbot\DOM\Node $startContainer Returns the node where the range begins.
 * @property-read int              $startOffset    Returns the position within the startContainer where the range
 *                                                 begins.
 * @property-read \Rowbot\DOM\Node $endContainer   Returns the node where the range ends.
 * @property-read int              $endOffset      Returns the position within the endContainer where the range ends.
 * @property-read bool             $collapsed      Returns true if the range's starting and ending points are at the
 *                                                 same position, otherwise false.
 */
abstract class AbstractRange
{
    protected RangeBoundary $range;

    public function __construct(BoundaryPoint $start, BoundaryPoint $end)
    {
        $this->range = new RangeBoundary($start, $end);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'startContainer' => $this->range->start->node,
            'startOffset' => $this->range->start->offset,
            'endContainer' => $this->range->end->node,
            'endOffset' => $this->range->end->offset,
            'collapsed' => $this->range->isCollapsed(),
            default => null,
        };
    }

    public function __clone(): void
    {
        $this->range = clone $this->range;
    }
}
