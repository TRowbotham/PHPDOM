<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
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

    /**
     * @dataProvider buildTests1
     */
    public function testSetStart(string $rangeEndpoints, string $nodes): void
    {
        [$range, $node, $offset] = $this->doEval($rangeEndpoints, $nodes);
        $this->checkSetStart($range, $node, $offset);
    }

    /**
     * @dataProvider buildTests1
     */
    public function testSetEnd(string $rangeEndpoints, string $nodes): void
    {
        [$range, $node, $offset] = $this->doEval($rangeEndpoints, $nodes);
        $this->checkSetEnd($range, $node, $offset);
    }

    /**
     * @dataProvider buildTests2
     */
    public function testSetStartBefore(string $rangeEndpoints, string $nodes): void
    {
        [$range, $node] = $this->doEval($rangeEndpoints, $nodes);
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

        $this->checkSetStart($range, $node->parentNode, $idx);
    }

    /**
     * @dataProvider buildTests2
     */
    public function testSetStartAfter(string $rangeEndpoints, string $nodes): void
    {
        [$range, $node] = $this->doEval($rangeEndpoints, $nodes);
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

        $this->checkSetStart($range, $node->parentNode, $idx + 1);
    }

    /**
     * @dataProvider buildTests2
     */
    public function testSetEndBefore(string $rangeEndpoints, string $nodes): void
    {
        [$range, $node] = $this->doEval($rangeEndpoints, $nodes);
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

        $this->checkSetEnd($range, $node->parentNode, $idx);
    }

    /**
     * @dataProvider buildTests2
     */
    public function testSetEndAfter(string $rangeEndpoints, string $nodes): void
    {
        [$range, $node] = $this->doEval($rangeEndpoints, $nodes);
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

        $this->checkSetEnd($range, $node->parentNode, $idx + 1);
    }

    public function checkSetStart(Range $range, Node $node, int $offset): void
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

    public function checkSetEnd(Range $range, Node $node, int $offset): void
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

    public function doEval(string $rangeEndpoints, string $nodes): array
    {
        $window = self::getWindow();
        $endpoints = $window->eval($rangeEndpoints);
        $range = Window::ownerDocument($endpoints[0])->createRange();
        $range->setStart($endpoints[0], $endpoints[1]);
        $range->setEnd($endpoints[2], $endpoints[3]);
        $evaled = $window->eval($nodes);

        if ($evaled instanceof Node) {
            return [$range, $evaled];
        }

        return [$range, $evaled[0], $evaled[1]];
    }

    public function buildTests1(): Generator
    {
        $window = self::getWindow();
        $window->initStrings();

        foreach ($window->testRangesShort as $i => $rangeEndpoints) {
            foreach ($window->testPoints as $j => $point) {
                yield [$rangeEndpoints, $point];
            }
        }
    }

    public function buildTests2(): Generator
    {
        $window = self::getWindow();
        $window->initStrings();

        foreach ($window->testRangesShort as $i => $rangeEndpoints) {
            foreach ($window->testNodesShort as $j => $node) {
                yield [$rangeEndpoints, $node];
            }
        }
    }

    public static function setUpBeforeClass(): void
    {
        self::getWindow()->setupRangeTests();
    }

    public static function getDocumentName(): string
    {
        return 'Range-set.html';
    }
}
