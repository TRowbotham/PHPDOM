<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-set.html
 */
class RangeSetTest extends RangeTestCase
{
    use WindowTrait;

    private static $startTests;
    private static $endTests;
    private static $startBeforeTests;
    private static $startAfterTests;
    private static $endBeforeTests;
    private static $endAfterTests;

    /**
     * @dataProvider setStartTestProvider
     */
    public function testSetStart(Range $range, Node $node, int $offset): void
    {
        if ($node->nodeType === Node::DOCUMENT_TYPE_NODE) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->setStart($node, $offset);
            }, InvalidNodeTypeError::class);

            return;
        }

        if ($offset < 0 || $offset > $node->getLength()) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->setStart($node, $offset);
            }, IndexSizeError::class);

            return;
        }

        $newRange = $range->cloneRange();
        $newRange->setStart($node, $offset);

        $this->assertSame($node, $newRange->startContainer);
        $this->assertSame($offset, $newRange->startOffset);

        if (
            $node->getRootNode() !== $range->startContainer->getRootNode()
            || $range->comparePoint($node, $offset) > 0
        ) {
            $this->assertSame($node, $newRange->endContainer);
            $this->assertSame($offset, $newRange->endOffset);
        } else {
            $this->assertSame($range->endContainer, $newRange->endContainer);
            $this->assertSame($range->endOffset, $newRange->endOffset);
        }
    }

    /**
     * @dataProvider setEndTestProvider
     */
    public function testSetEnd(Range $range, Node $node, int $offset): void
    {
        if ($node->nodeType === Node::DOCUMENT_TYPE_NODE) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->setEnd($node, $offset);
            }, InvalidNodeTypeError::class);

            return;
        }

        if ($offset < 0 || $offset > $node->getLength()) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->setEnd($node, $offset);
            }, IndexSizeError::class);

            return;
        }

        $newRange = $range->cloneRange();
        $newRange->setEnd($node, $offset);

        if (
            $node->getRootNode() !== $range->startContainer->getRootNode()
            || $range->comparePoint($node, $offset) < 0
        ) {
            $this->assertSame($node, $newRange->startContainer);
            $this->assertSame($offset, $newRange->startOffset);
        } else {
            $this->assertSame($range->startContainer, $newRange->startContainer);
            $this->assertSame($range->startOffset, $newRange->startOffset);
        }

        $this->assertSame($node, $newRange->endContainer);
        $this->assertSame($offset, $newRange->endOffset);
    }

    /**
     * @dataProvider setStartBeforeTestProvider
     */
    public function testSetStartBefore(Range $range, Node $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->setStartBefore($node);
            }, InvalidNodeTypeError::class);

            return;
        }

        $idx = 0;

        while ($node->parentNode->childNodes[$idx] !== $node) {
            ++$idx;
        }

        $this->testSetStart($range, $node->parentNode, $idx);
    }

    /**
     * @dataProvider setStartAfterTestProvider
     */
    public function testSetStartAfter(Range $range, Node $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->setStartAfter($node);
            }, InvalidNodeTypeError::class);

            return;
        }

        $idx = 0;

        while ($node->parentNode->childNodes[$idx] !== $node) {
            ++$idx;
        }

        $this->testSetStart($range, $node->parentNode, $idx + 1);
    }

    /**
     * @dataProvider setEndBeforeTestProvider
     */
    public function testSetEndBefore(Range $range, Node $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->setEndBefore($node);
            }, InvalidNodeTypeError::class);

            return;
        }

        $idx = 0;

        while ($node->parentNode->childNodes[$idx] !== $node) {
            ++$idx;
        }

        $this->testSetEnd($range, $node->parentNode, $idx);
    }

    /**
     * @dataProvider setEndAfterTestProvider
     */
    public function testSetEndAfter(Range $range, Node $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->setEndAfter($node);
            }, InvalidNodeTypeError::class);

            return;
        }

        $idx = 0;

        while ($node->parentNode->childNodes[$idx] !== $node) {
            ++$idx;
        }

        $this->testSetEnd($range, $node->parentNode, $idx + 1);
    }

    public function setStartTestProvider(): array
    {
        $this->generateTests();

        return self::$startTests;
    }

    public function setEndTestProvider(): array
    {
        $this->generateTests();

        return self::$endTests;
    }

    public function setStartBeforeTestProvider(): array
    {
        $this->generateTests();

        return self::$startBeforeTests;
    }

    public function setStartAfterTestProvider(): array
    {
        $this->generateTests();

        return self::$startAfterTests;
    }

    public function setEndBeforeTestProvider(): array
    {
        $this->generateTests();

        return self::$endBeforeTests;
    }

    public function setEndAfterTestProvider(): array
    {
        $this->generateTests();

        return self::$endAfterTests;
    }

    public function generateTests(): void
    {
        static $testSetupComplete = false;

        if ($testSetupComplete) {
            return;
        }

        $testSetupComplete = true;
        $window = self::getWindow();
        $window->setupRangeTests();
        self::$startTests = [];
        self::$endTests = [];
        self::$startBeforeTests = [];
        self::$startAfterTests = [];
        self::$endBeforeTests = [];
        self::$endAfterTests = [];
        $testPointsCached = array_map(function (string $points) use ($window) {
            return $window->eval($points);
        }, $window->testPoints);
        $testNodesCached = array_map(function (string $points) use ($window) {
            return $window->eval($points);
        }, $window->testNodesShort);

        for ($i = 0, $len1 = count($window->testRangesShort); $i < $len1; ++$i) {
            $endpoints = $window->eval($window->testRangesShort[$i]);
            $range = Window::ownerDocument($endpoints[0])->createRange();
            $range->setStart($endpoints[0], $endpoints[1]);
            $range->setEnd($endpoints[2], $endpoints[3]);

            for ($j = 0, $len2 = count($window->testPoints); $j < $len2; ++$j) {
                self::$startTests[] = [$range, $testPointsCached[$j][0], $testPointsCached[$j][1]];
                self::$endTests[] = [$range, $testPointsCached[$j][0], $testPointsCached[$j][1]];
            }

            for ($j = 0, $len3 = count($window->testNodesShort); $j < $len3; ++$j) {
                self::$startBeforeTests[] = [$range, $testNodesCached[$j]];
                self::$startAfterTests[] = [$range, $testNodesCached[$j]];
                self::$endBeforeTests[] = [$range, $testNodesCached[$j]];
                self::$endAfterTests[] = [$range, $testNodesCached[$j]];
            }
        }

        self::registerCleanup(static function (): void {
            self::$startTests = null;
            self::$endTests = null;
            self::$startBeforeTests = null;
            self::$startAfterTests = null;
            self::$endBeforeTests = null;
            self::$endAfterTests = null;
        });
    }

    public static function getDocumentName(): string
    {
        return 'Range-set.html';
    }
}
