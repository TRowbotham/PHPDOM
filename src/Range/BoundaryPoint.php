<?php

declare(strict_types=1);

namespace Rowbot\DOM\Range;

use Rowbot\DOM\Node;

use function assert;

/**
 * @see https://dom.spec.whatwg.org/#boundary-points
 */
class BoundaryPoint
{
    public Node $node;

    public int $offset;

    public function __construct(Node $node, int $offset)
    {
        $this->node = $node;
        $this->offset = $offset;
    }

    /**
     * @see https://dom.spec.whatwg.org/#concept-range-bp-position
     */
    public static function comparePosition(self $a, self $b): Position
    {
        // 1. Assert: nodeA and nodeB have the same root.
        assert($a->node->getRootNode() === $b->node->getRootNode());

        // 2. If nodeA is nodeB, then return equal if offsetA is offsetB, before if offsetA is less than offsetB, and
        // after if offsetA is greater than offsetB.
        if ($a->node === $b->node) {
            if ($a->offset === $b->offset) {
                return Position::EQUAL;
            }

            if ($a->offset < $b->offset) {
                return Position::BEFORE;
            }

            return Position::AFTER;
        }

        // 3. If nodeA is following nodeB, then if the position of (nodeB, offsetB) relative to (nodeA, offsetA) is
        // before, return after, and if it is after, return before.
        if ($a->node->followsNode($b->node)) {
            $position = self::comparePosition($b, $a);

            if ($position === Position::BEFORE) {
                return Position::AFTER;
            }

            if ($position === Position::AFTER) {
                return Position::BEFORE;
            }
        }

        // 4. If nodeA is an ancestor of nodeB:
        if ($a->node->isAncestorOf($b->node)) {
            // 4.1. Let child be nodeB.
            $child = $b->node;

            // 2. While child is not a child of nodeA, set child to its parent.
            while ($child->parentNode !== $a->node) {
                $child = $child->parentNode;
            }

            // 4.3. If child’s index is less than offsetA, then return after.
            if ($child->getTreeIndex() < $a->offset) {
                return Position::AFTER;
            }
        }

        // 5. Return before.
        return Position::BEFORE;
    }
}
