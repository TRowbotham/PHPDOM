<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function pow;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-isPointInRange.html
 */
class RangeIsPointInRangeTest extends RangeTestCase
{
    use WindowTrait;

    private static $testRangesCached;

    /**
     * @dataProvider pointsProvider
     */
    public function testIsPointInRange(Node $node, int $offset): void
    {
        // isPointInRange is an unsigned long, so per WebIDL, we need to treat it
        // as though it wrapped to an unsigned 32-bit integer.
        $normalizedOffset = $offset % pow(2, 32);

        if ($normalizedOffset < 0) {
            $normalizedOffset += pow(2, 32);
        }

        foreach (self::$testRangesCached as $range) {
            $range = $range->cloneRange();

            // "If node's root is different from the context object's root,
            // return false and terminate these steps."
            if ($node->getRootNode() !== $range->startContainer->getRootNode()) {
                $this->assertFalse($range->isPointInRange($node, $offset));

                return;
            }

            // "If node is a doctype, throw an "InvalidNodeTypeError" exception
            // and terminate these steps."
            if ($node->nodeType === Node::DOCUMENT_TYPE_NODE) {
                $this->assertThrows(static function () use ($range, $node, $offset): void {
                    $range->isPointInRange($node, $offset);
                }, InvalidNodeTypeError::class);

                return;
            }

            // "If offset is greater than node's length, throw an
            // "IndexSizeError" exception and terminate these steps."
            if ($normalizedOffset > $node->getLength()) {
                $this->assertThrows(static function () use ($range, $node, $offset): void {
                    $range->isPointInRange($node, $offset);
                }, IndexSizeError::class);

                return;
            }

            // "If (node, offset) is before start or after end, return false
            // and terminate these steps."
            if (
                Window::getPosition($node, $normalizedOffset, $range->startContainer, $range->startOffset) === 'before'
                || Window::getPosition($node, $normalizedOffset, $range->endContainer, $range->endOffset) === 'after'
            ) {
                $this->assertFalse($range->isPointInRange($node, $offset));

                return;
            }

            $this->assertTrue($range->isPointInRange($node, $offset));
        }
    }

    public function pointsProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();
        self::$testRangesCached = [];

        foreach ($window->testRanges as $range) {
            self::$testRangesCached[] = Window::rangeFromEndpoints($window->eval($range));
        }

        $detachedRange = $window->document->createRange();
        $detachedRange->detach();
        $window->testRanges[] = '"detached"';
        self::$testRangesCached[] = $detachedRange;

        self::registerCleanup(static function (): void {
            self::$testRangesCached = null;
        });

        foreach ($window->testPoints as $testPoint) {
            $evaled = $window->eval($testPoint);
            yield [$evaled[0], $evaled[1]];
        }
    }

    public static function getDocumentName(): string
    {
        return 'Range-isPointInRange.html';
    }
}
