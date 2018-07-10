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
     * @var \Rowbot\DOM\Node
     */
    protected $startNode;

    /**
     * @var int
     */
    protected $startOffset;

    /**
     * @var \Rowbot\DOM\Node
     */
    protected $endNode;

    /**
     * @var int
     */
    protected $endOffset;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'startContainer') {
            return $this->startNode;
        }

        if ($name === 'startOffset') {
            return $this->startOffset;
        }

        if ($name === 'endContainer') {
            return $this->endNode;
        }

        if ($name === 'endOffset') {
            return $this->endOffset;
        }

        if ($name === 'collapsed') {
            return $this->isCollapsed();
        }
    }

    /**
     * Determines if a range is collapsed.
     *
     * @see https://dom.spec.whatwg.org/#range-collapsed
     *
     * @return bool True if collapsed, False otherwise.
     */
    protected function isCollapsed(): bool
    {
        return $this->startNode === $this->endNode
            && $this->startOffset === $this->endOffset;
    }
}
