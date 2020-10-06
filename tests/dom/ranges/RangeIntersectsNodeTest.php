<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Generator;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-intersectsNode.html
 */
class RangeIntersectsNodeTest extends RangeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider intersectionNodeProvider
     */
    public function testIntersectsNode(Node $node, Range $range): void
    {
        $this->assertNotNull($range);

        $range = $range->cloneRange();

        // "If node's root is different from the context object's root,
        // return false and terminate these steps."
        if ($node->getRootNode() !== $range->startContainer->getRootNode()) {
            $this->assertFalse($range->intersectsNode($node));

            return;
        }

        // "Let parent be node's parent."
        $parent_ = $node->parentNode;

        // "If parent is null, return true and terminate these steps."
        if (!$parent_) {
            $this->assertTrue($range->intersectsNode($node));

            return;
        }

        // "Let offset be node's index."
        $offset = $node->getTreeIndex();

        // "If (parent, offset) is before end and (parent, offset + 1) is
        // after start, return true and terminate these steps."
        if (
            Window::getPosition($parent_, $offset, $range->endContainer, $range->endOffset) === 'before'
            && Window::getPosition($parent_, $offset + 1, $range->startContainer, $range->startOffset) === 'after'
        ) {
            $this->assertTrue($range->intersectsNode($node));

            return;
        }

        $this->assertFalse($range->intersectsNode($node));
    }

    public function intersectionNodeProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        foreach ($window->testNodes as $node) {
            $node = $window->eval($node);

            foreach ($window->testRanges as $range) {
                try {
                    $range = Window::rangeFromEndpoints($window->eval($range));
                } catch (Exception $e) {
                    $range = null;
                }

                yield [$node, $range];
            }
        }
    }

    public static function getDocumentName(): string
    {
        return 'Range-intersectsNode.html';
    }
}
