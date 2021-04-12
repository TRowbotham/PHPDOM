<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Exception\DOMException;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-splitText.html
 */
class Range_mutations_splitTextTest extends RangeTestCase
{
    use Range_mutationTrait;

    public function rangeProvider(): array
    {
        $splitTextTests = [];

        foreach ($this->textNodes() as $node) {
            $splitTextTests[] = [$node, 376, $node, 0, $node, 1];
            $splitTextTests[] = [$node, 0, $node, 0, $node, 0];
            $splitTextTests[] = [$node, 1, $node, 1, $node, 1];
            $splitTextTests[] = [$node, $node . "->length", $node, $node . "->length", $node, $node . "->length"];
            $splitTextTests[] = [$node, 1, $node, 1, $node, 3];
            $splitTextTests[] = [$node, 2, $node, 1, $node, 3];
            $splitTextTests[] = [$node, 3, $node, 1, $node, 3];
        }

        $splitTextTests[] = ["paras[0]->firstChild", 1, "paras[0]", 0, "paras[0]", 0];
        $splitTextTests[] = ["paras[0]->firstChild", 1, "paras[0]", 0, "paras[0]", 1];
        $splitTextTests[] = ["paras[0]->firstChild", 1, "paras[0]", 1, "paras[0]", 1];
        $splitTextTests[] = ["paras[0]->firstChild", 1, "paras[0]->firstChild", 1, "paras[0]", 1];
        $splitTextTests[] = ["paras[0]->firstChild", 2, "paras[0]->firstChild", 1, "paras[0]", 1];
        $splitTextTests[] = ["paras[0]->firstChild", 3, "paras[0]->firstChild", 1, "paras[0]", 1];
        $splitTextTests[] = ["paras[0]->firstChild", 1, "paras[0]", 0, "paras[0]->firstChild", 3];
        $splitTextTests[] = ["paras[0]->firstChild", 2, "paras[0]", 0, "paras[0]->firstChild", 3];
        $splitTextTests[] = ["paras[0]->firstChild", 3, "paras[0]", 0, "paras[0]->firstChild", 3];

        return $this->doTests($splitTextTests, static function ($params) {
            return $params[0] . ".splitText(" . $params[1] . ")";
        }, [$this, '_testSplitText']);
    }

    protected function _testSplitText($oldNode, $offset, $startContainer, $startOffset, $endContainer, $endOffset): array
    {
        // Save these for later
        $originalStartOffset = $startOffset;
        $originalEndOffset = $endOffset;
        $originalLength = $oldNode->length;

        $newNode;

        try {
            $newNode = $oldNode->splitText($offset);
        } catch (DOMException $e) {
            // Should only happen if offset is negative
            return [$startContainer, $startOffset, $endContainer, $endOffset];
        }

        // First we adjust for replacing data:
        //
        // "Replace data with offset offset, count count, and data the empty
        // string."
        //
        // That translates to offset = offset, count = originalLength - offset,
        // data = "".  node is $oldNode.
        //
        // "For every boundary point whose node is node, and whose offset is
        // greater than offset but less than or equal to offset plus count, set its
        // offset to offset."
        if (
            $startContainer == $oldNode
            && $startOffset > $offset
            && $startOffset <= $originalLength
        ) {
            $startOffset = $offset;
        }

        if (
            $endContainer == $oldNode
            && $endOffset > $offset
            && $endOffset <= $originalLength
        ) {
            $endOffset = $offset;
        }

        // "For every boundary point whose node is node, and whose offset is
        // greater than offset plus count, add the length of data to its offset,
        // then subtract count from it."
        //
        // Can't happen: offset plus count is originalLength.

        // Now we insert a node, if oldNode's parent isn't null: "For each boundary
        // point whose node is the new parent of the affected node and whose offset
        // is greater than the new index of the affected node, add one to the
        // boundary point's offset."
        if (
            $startContainer === $oldNode->parentNode
            && $startOffset > 1 + self::getWindow()->indexOf($oldNode)
        ) {
            $startOffset++;
        }

        if (
            $endContainer === $oldNode->parentNode
            && $endOffset > 1 + self::getWindow()->indexOf($oldNode)
        ) {
            $endOffset++;
        }

        // Finally, the splitText stuff itself:
        //
        // "If parent is not null, run these substeps:
        //
        //   * "For each range whose start node is node and start offset is greater
        //   than offset, set its start node to new node and decrease its start
        //   offset by offset.
        //
        //   * "For each range whose end node is node and end offset is greater
        //   than offset, set its end node to new node and decrease its end offset
        //   by offset.
        //
        //   * "For each range whose start node is parent and start offset is equal
        //   to the index of node + 1, increase its start offset by one.
        //
        //   * "For each range whose end node is parent and end offset is equal to
        //   the index of node + 1, increase its end offset by one."
        if ($oldNode->parentNode) {
            if ($startContainer == $oldNode && $originalStartOffset > $offset) {
                $startContainer = $newNode;
                $startOffset = $originalStartOffset - $offset;
            }

            if ($endContainer == $oldNode && $originalEndOffset > $offset) {
                $endContainer = $newNode;
                $endOffset = $originalEndOffset - $offset;
            }

            if ($startContainer == $oldNode->parentNode && $startOffset == 1 + self::getWindow()->indexOf($oldNode)) {
                $startOffset++;
            }

            if ($endContainer == $oldNode->parentNode && $endOffset == 1 + self::getWindow()->indexOf($oldNode)) {
                $endOffset++;
            }
        }

        return [$startContainer, $startOffset, $endContainer, $endOffset];
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
