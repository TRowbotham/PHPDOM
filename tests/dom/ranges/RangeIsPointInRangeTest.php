<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function array_unshift;
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
    public function testIsPointInRange(string $node, string $range): void
    {
        $window = self::getWindow();
        [$node, $offset] = $window->eval($node);

        if ($range === '"detached"') {
            $range = $window->document->createRange();
            $range->detach();
        } else {
            $range = Window::rangeFromEndpoints($window->eval($range));
        }

        // isPointInRange is an unsigned long, so per WebIDL, we need to treat it
        // as though it wrapped to an unsigned 32-bit integer.
        $normalizedOffset = $offset % pow(2, 32);

        if ($normalizedOffset < 0) {
            $normalizedOffset += pow(2, 32);
        }

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

    public function pointsProvider(): Generator
    {
        $window = self::getWindow();
        $window->initStrings();

        array_unshift($window->testRanges, '"detached"');

        foreach ($window->testPoints as $testPoint) {
            foreach ($window->testRanges as $range) {
                yield [$testPoint, $range];
            }
        }
    }

    public static function setUpBeforeClass(): void
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        array_unshift($window->testRanges, '"detached"');
    }

    public static function getDocumentName(): string
    {
        return 'Range-isPointInRange.html';
    }
}
