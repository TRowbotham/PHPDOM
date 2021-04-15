<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Throwable;

use function pow;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-comparePoint.html
 */
class RangeComparePointTest extends RangeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider pointsProvider
     */
    public function testComparePoint(string $testPoint, string $testRange): void
    {
        $window = self::getWindow();
        [$node, $offset] = $window->eval($testPoint);

        try {
            $range = Window::rangeFromEndpoints($window->eval($testRange));
        } catch (Throwable $e) {
            $range = null;
        }

        // comparePoint is an unsigned long, so per WebIDL, we need to treat it as
        // though it wrapped to an unsigned 32-bit integer.
        $normalizedOffset = $offset % pow(2, 32);

        if ($normalizedOffset < 0) {
            $normalizedOffset += pow(2, 32);
        }

        $this->assertNotNull($range);

        $range = $range->cloneRange();

        // "If node's root is different from the context object's root,
        // throw a "WrongDocumentError" exception and terminate these
        // steps."
        if (Window::furthestAncestor($node) !== Window::furthestAncestor($range->startContainer)) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->comparePoint($node, $offset);
            }, WrongDocumentError::class);

            return;
        }

        // "If node is a doctype, throw an "InvalidNodeTypeError" exception
        // and terminate these steps."
        if ($node->nodeType === Node::DOCUMENT_TYPE_NODE) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->comparePoint($node, $offset);
            }, InvalidNodeTypeError::class);

            return;
        }

        // "If offset is greater than node's length, throw an
        // "IndexSizeError" exception and terminate these steps."
        if ($normalizedOffset > $node->getLength()) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->comparePoint($node, $offset);
            }, IndexSizeError::class);

            return;
        }

        // "If (node, offset) is before start, return −1 and terminate
        // these steps."
        if (Window::getPosition($node, $normalizedOffset, $range->startContainer, $range->startOffset) === 'before') {
            $this->assertSame(-1, $range->comparePoint($node, $offset));

            return;
        }

        // "If (node, offset) is after end, return 1 and terminate these
        // steps."
        if (Window::getPosition($node, $normalizedOffset, $range->endContainer, $range->endOffset) === 'after') {
            $this->assertSame(1, $range->comparePoint($node, $offset));

            return;
        }

        // "Return 0."
        $this->assertSame(0, $range->comparePoint($node, $offset));
    }

    public function pointsProvider(): Generator
    {
        $window = self::getWindow();
        $window->initStrings();

        foreach ($window->testPoints as $point) {
            foreach ($window->testRanges as $range) {
                yield [$point, $range];
            }
        }
    }

    public static function setUpBeforeClass(): void
    {
        self::getWindow()->setupRangeTests();
    }

    public static function getDocumentName(): string
    {
        return 'Range-comparePoint.html';
    }
}
