<?php

declare(strict_types=1);

namespace Rowbot\DOM;

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
    /**
     * @var \Rowbot\DOM\RangeBoundary
     */
    protected $range;

    public function __construct(RangeBoundary $range)
    {
        $this->range = $range;
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'startContainer') {
            return $this->range->startNode;
        }

        if ($name === 'startOffset') {
            return $this->range->startOffset;
        }

        if ($name === 'endContainer') {
            return $this->range->endNode;
        }

        if ($name === 'endOffset') {
            return $this->range->endOffset;
        }

        if ($name === 'collapsed') {
            return $this->isCollapsed();
        }
    }

    /**
     * Determines if a range is collapsed.
     *
     * @see https://dom.spec.whatwg.org/#range-collapsed
     */
    protected function isCollapsed(): bool
    {
        return $this->range->startNode === $this->range->endNode
            && $this->range->startOffset === $this->range->endOffset;
    }
}
