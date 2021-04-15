<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Closure;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function array_map;
use function array_merge;
use function count;
use function mb_strlen;
use function mb_substr;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations.js
 */
trait Range_mutationTrait
{
    use WindowTrait;

    // Give a textual description of the range we're testing, for the test names.
    protected function describeRange($startContainer, $startOffset, $endContainer, $endOffset): string
    {
        if ($startContainer === $endContainer && $startOffset === $endOffset) {
            return "range collapsed at (" . $startContainer . ", " . $startOffset . ")";
        } elseif ($startContainer === $endContainer) {
            return "range on " . $startContainer . " from " . $startOffset . " to " . $endOffset;
        } else {
            return "range from (" . $startContainer . ", " . $startOffset . ") to (" . $endContainer . ", " . $endOffset . ")";
        }
    }

    protected function textNodes(): array
    {
        return [
            "paras[0]->firstChild",
            "paras[1]->firstChild",
            "foreignTextNode",
            "xmlTextNode",
            "detachedTextNode",
            "detachedForeignTextNode",
            "detachedXmlTextNode",
        ];
    }

    protected function commentNodes(): array
    {
        return [
            "comment",
            "foreignComment",
            "xmlComment",
            "detachedComment",
            "detachedForeignComment",
            "detachedXmlComment",
        ];
    }

    protected function characterDataNodes(): array
    {
        return array_merge($this->textNodes(), $this->commentNodes());
    }

    public function doTests(array $sourceTests, Closure $descFn, callable $testFn): array
    {
        $tests = [];
        $window = self::getWindow();
        $window->setupRangeTests(true);

        foreach ($sourceTests as $params) {
            $len = count($params);
            $tests[] = [
                // $descFn($params) . ', with unselected ' . $this->describeRange($params[$len - 4], $params[$len - 3], $params[$len - 2], $params[$len - 1]),
                // The closure here ensures that the params that testFn get are the
                // current version of params, not the version from the last
                // iteration of this loop.  We test that none of the parameters
                // evaluate to undefined to catch bugs in our eval'ing, like
                // mistyping a property name.
                (static function ($params) use ($window, $testFn) {
                    return static function () use ($window, $testFn, $params) {
                        $evaledParams = array_map([$window, 'eval'], $params);

                        foreach ($evaledParams as $param) {
                            self::assertNotNull($param);
                        }

                        return $testFn(...$evaledParams);
                    };
                })($params),
                false,
                $params[$len - 4],
                $params[$len - 3],
                $params[$len - 2],
                $params[$len - 1],
            ];
            // $tests[] = [
            //     $descFn($params) . ', with selected ' . $this->describeRange($params[$len - 4], $params[$len - 3], $params[$len - 2], $params[$len - 1]),
            //     (static function ($params) use ($window, $testFn) {
            //         return static function ($selectedRange) use ($window, $testFn, $params) {
            //             $evaledParams = array_map([$window, 'eval'], $params);

            //             foreach ($evaledParams as $param) {
            //                 self::assertNotNull($param);
            //             }

            //             // Override input range with the one that was actually selected when computing the expected result.
            //             $evaledParams = array_merge($evaledParams, [$selectedRange->startContainer, $selectedRange->startOffset, $selectedRange->endContainer, $selectedRange->endOffset]);

            //             return $testFn(...$evaledParams);
            //         };
            //     })($params),
            //     true,
            //     $params[$len - 4],
            //     $params[$len - 3],
            //     $params[$len - 2],
            //     $params[$len - 1],
            // ];
        }

        return $tests;
    }

    /**
     * Set up the range, call the callback function to do the DOM modification and
     * tell us what to expect.  The callback function needs to return a
     * four-element array with the expected start/end containers/offsets, and
     * receives no arguments.  useSelection tells us whether the Range should be
     * added to a Selection and the Selection tested to ensure that the mutation
     * affects user selections as well as other ranges; every test is run with this
     * both false and true, because when it's set to true WebKit and Opera fail all
     * tests' sanity checks, which is unhelpful.  The last four parameters just
     * tell us what range to build.
     *
     * @dataProvider rangeProvider
     *
     * @test
     */
    public function doTest($callback, $useSelection, $startContainer, $startOffset, $endContainer, $endOffset): void
    {
        $window = self::getWindow();
        // Recreate all the test nodes in case they were altered by the last test run.
        $window->setupRangeTests(true);
        $startContainer = $window->eval($startContainer);
        $startOffset = $window->eval($startOffset);
        $endContainer = $window->eval($endContainer);
        $endOffset = $window->eval($endOffset);

        $ownerDoc = $startContainer->nodeType === Node::DOCUMENT_NODE
            ? $startContainer
            : $startContainer->ownerDocument;
        $range = $ownerDoc->createRange();
        $range->setStart($startContainer, $startOffset);
        $range->setEnd($endContainer, $endOffset);

        if ($useSelection) {
            $window->getSelection()->removeAllRanges();
            $window->getSelection()->addRange($range);

            // Some browsers refuse to add a range unless it results in an actual visible selection.
            if (!$window->getSelection()->rangeCount) {
                return;
            }

            // Override range with the one that was actually selected as it differs in some browsers.
            $range = $window->getSelection()->getRangeAt(0);
        }

        $expected = $callback($range);

        self::assertSame($expected[0], $range->startContainer);
        self::assertSame($expected[1], $range->startOffset);
        self::assertSame($expected[2], $range->endContainer);
        self::assertSame($expected[3], $range->endOffset);
    }

    protected function _testReplaceDataAlgorithm($node, $offset, $count, $data, $callback, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        // Mutation works the same any time DOM Core's "replace data" algorithm is
        // invoked.  node, offset, count, data are as in that algorithm.  The
        // callback is what does the actual setting.  Not to be confused with
        // testReplaceData, which tests the replaceData() method.

        // Barring any provision to the contrary, the containers and offsets must
        // not change.
        $expectedStartContainer = $startContainer;
        $expectedStartOffset = $startOffset;
        $expectedEndContainer = $endContainer;
        $expectedEndOffset = $endOffset;

        $originalParent = $node->parentNode;
        $originalData = $node->data;

        $exceptionThrown = false;

        try {
            $callback();
        } catch (DOMException $e) {
            // Should only happen if offset is greater than length
            $exceptionThrown = true;
        }

        self::assertSame(
            $originalParent,
            $node->parentNode,
            "Sanity check failed: changing data changed the parent"
        );

        // "User agents must run the following steps whenever they replace data of
        // a CharacterData node, as though they were written in the specification
        // for that algorithm after all other steps. In particular, the steps must
        // not be executed if the algorithm threw an exception."
        if ($exceptionThrown) {
            self::assertSame(
                $originalData,
                $node->data,
                "Sanity check failed: exception thrown but data changed"
            );
        } else {
            self::assertSame(
                mb_substr($originalData, 0, $offset, 'utf-8') . $data . mb_substr($originalData, $offset + $count, null, 'utf-8'),
                $node->data,
                "Sanity check failed: data not changed as expected"
            );
        }

        // "For every boundary point whose node is node, and whose offset is
        // greater than offset but less than or equal to offset plus count, set
        // its offset to offset."
        if (
            !$exceptionThrown
            && $startContainer === $node
            && $startOffset > $offset
            && $startOffset <= $offset + $count
        ) {
            $expectedStartOffset = $offset;
        }

        if (
            !$exceptionThrown
            && $endContainer === $node
            && $endOffset > $offset
            && $endOffset <= $offset + $count
        ) {
            $expectedEndOffset = $offset;
        }

        // "For every boundary point whose node is node, and whose offset is
        // greater than offset plus count, add the length of data to its offset,
        // then subtract count from it."
        if (
            !$exceptionThrown
            && $startContainer === $node
            && $startOffset > $offset + $count
        ) {
            $expectedStartOffset += mb_strlen($data, 'utf-8') - $count;
        }

        if (
            !$exceptionThrown
            && $endContainer === $node
            && $endOffset > $offset + $count
        ) {
            $expectedEndOffset += mb_strlen($data, 'utf-8') - $count;
        }

        return [$expectedStartContainer, $expectedStartOffset, $expectedEndContainer, $expectedEndOffset];
    }

    // If we were to remove removedNode from its parent, what would the boundary
    // point [node, offset] become?  Returns [new node, new offset].  Must be
    // called BEFORE the node is actually removed, so its parent is not null.  (If
    // the parent is null, it will do nothing.)
    public function modifyForRemove($removedNode, $point)
    {
        $oldParent = $removedNode->parentNode;
        $oldIndex = self::getWindow()->indexOf($removedNode);

        if (!$oldParent) {
            return $point;
        }

        // "For each boundary point whose node is removed node or a descendant of
        // it, set the boundary point to (old parent, old index)."
        if ($point[0] === $removedNode || self::getWindow()->isDescendant($point[0], $removedNode)) {
            return [$oldParent, $oldIndex];
        }

        // "For each boundary point whose node is old parent and whose offset is
        // greater than old index, subtract one from its offset."
        if ($point[0] === $oldParent && $point[1] > $oldIndex) {
            return [$point[0], $point[1] - 1];
        }

        return $point;
    }


    // Update the given boundary point [node, offset] to account for the fact that
    // insertedNode was just inserted into its current position.  This must be
    // called AFTER insertedNode was already inserted.
    public function modifyForInsert($insertedNode, $point)
    {
        // "For each boundary point whose node is the new parent of the affected
        // node and whose offset is greater than the new index of the affected
        // node, add one to the boundary point's offset."
        if ($point[0] === $insertedNode->parentNode && $point[1] > self::getWindow()->indexOf($insertedNode)) {
            return [$point[0], $point[1] + 1];
        }

        return $point;
    }
}
