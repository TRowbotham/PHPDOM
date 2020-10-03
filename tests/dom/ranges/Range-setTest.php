<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-selectNode.html
 */
class RangeSetTest extends RangeTestCase
{
    protected static $runAutoSetup = true;
    protected static $testsSetup = false;

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
        global $testPoints, $testNodesShort, $testRangesShort;
        static $testSetupComplete = false;

        if ($testSetupComplete) {
            return;
        }

        // Hack around the fact that setupBeforeClass() is called after dataProviders
        self::setUpBeforeClass();
        $testSetupComplete = true;
        self::$startTests = [];
        self::$endTests = [];
        self::$startBeforeTests = [];
        self::$startAfterTests = [];
        self::$endBeforeTests = [];
        self::$endAfterTests = [];
        $testPointsCached = array_map(function (string $points) {
            return $this->eval($points, self::$document);
        }, $testPoints);
        $testNodesCached = array_map(function (string $points) {
            return $this->eval($points, self::$document);
        }, $testNodesShort);

        for ($i = 0, $len1 = count($testRangesShort); $i < $len1; ++$i) {
            $endpoints = $this->eval($testRangesShort[$i], self::$document);
            $range = $this->ownerDocument($endpoints[0])->createRange();
            $range->setStart($endpoints[0], $endpoints[1]);
            $range->setEnd($endpoints[2], $endpoints[3]);

            for ($j = 0, $len2 = count($testPoints); $j < $len2; ++$j) {
                self::$startTests[] = [$range, $testPointsCached[$j][0], $testPointsCached[$j][1]];
                self::$endTests[] = [$range, $testPointsCached[$j][0], $testPointsCached[$j][1]];
            }

            for ($j = 0, $len3 = count($testNodesShort); $j < $len3; ++$j) {
                self::$startBeforeTests[] = [$range, $testNodesCached[$j]];
                self::$startAfterTests[] = [$range, $testNodesCached[$j]];
                self::$endBeforeTests[] = [$range, $testNodesCached[$j]];
                self::$endAfterTests[] = [$range, $testNodesCached[$j]];
            }
        }
    }

    public static function fetchDocument(): string
    {
        return <<<'TEST_HTML'
<!doctype html>
<title>Range setting tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>

<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

function testSetStart(range, node, offset) {
    if (node.nodeType == Node.DOCUMENT_TYPE_NODE) {
    assert_throws_dom("INVALID_NODE_TYPE_ERR", function() {
        range.setStart(node, offset);
    }, "setStart() to a doctype must throw INVALID_NODE_TYPE_ERR");
    return;
    }

    if (offset < 0 || offset > nodeLength(node)) {
    assert_throws_dom("INDEX_SIZE_ERR", function() {
        range.setStart(node, offset);
    }, "setStart() to a too-large offset must throw INDEX_SIZE_ERR");
    return;
    }

    var newRange = range.cloneRange();
    newRange.setStart(node, offset);

    assert_equals(newRange.startContainer, node,
    "setStart() must change startContainer to the new node");
    assert_equals(newRange.startOffset, offset,
    "setStart() must change startOffset to the new offset");

    // FIXME: I'm assuming comparePoint() is correct, but the tests for that
    // will depend on setStart()/setEnd().
    if (furthestAncestor(node) != furthestAncestor(range.startContainer)
    || range.comparePoint(node, offset) > 0) {
    assert_equals(newRange.endContainer, node,
        "setStart(node, offset) where node is after current end or in different document must set the end node to node too");
    assert_equals(newRange.endOffset, offset,
        "setStart(node, offset) where node is after current end or in different document must set the end offset to offset too");
    } else {
    assert_equals(newRange.endContainer, range.endContainer,
        "setStart() must not change the end node if the new start is before the old end");
    assert_equals(newRange.endOffset, range.endOffset,
        "setStart() must not change the end offset if the new start is before the old end");
    }
}

function testSetEnd(range, node, offset) {
    if (node.nodeType == Node.DOCUMENT_TYPE_NODE) {
    assert_throws_dom("INVALID_NODE_TYPE_ERR", function() {
        range.setEnd(node, offset);
    }, "setEnd() to a doctype must throw INVALID_NODE_TYPE_ERR");
    return;
    }

    if (offset < 0 || offset > nodeLength(node)) {
    assert_throws_dom("INDEX_SIZE_ERR", function() {
        range.setEnd(node, offset);
    }, "setEnd() to a too-large offset must throw INDEX_SIZE_ERR");
    return;
    }

    var newRange = range.cloneRange();
    newRange.setEnd(node, offset);

    // FIXME: I'm assuming comparePoint() is correct, but the tests for that
    // will depend on setStart()/setEnd().
    if (furthestAncestor(node) != furthestAncestor(range.startContainer)
    || range.comparePoint(node, offset) < 0) {
    assert_equals(newRange.startContainer, node,
        "setEnd(node, offset) where node is before current start or in different document must set the end node to node too");
    assert_equals(newRange.startOffset, offset,
        "setEnd(node, offset) where node is before current start or in different document must set the end offset to offset too");
    } else {
    assert_equals(newRange.startContainer, range.startContainer,
        "setEnd() must not change the start node if the new end is after the old start");
    assert_equals(newRange.startOffset, range.startOffset,
        "setEnd() must not change the start offset if the new end is after the old start");
    }

    assert_equals(newRange.endContainer, node,
    "setEnd() must change endContainer to the new node");
    assert_equals(newRange.endOffset, offset,
    "setEnd() must change endOffset to the new offset");
}

function testSetStartBefore(range, node) {
    var parent = node.parentNode;
    if (parent === null) {
    assert_throws_dom("INVALID_NODE_TYPE_ERR", function () {
        range.setStartBefore(node);
    }, "setStartBefore() to a node with null parent must throw INVALID_NODE_TYPE_ERR");
    return;
    }

    var idx = 0;
    while (node.parentNode.childNodes[idx] != node) {
    idx++;
    }

    testSetStart(range, node.parentNode, idx);
}

function testSetStartAfter(range, node) {
    var parent = node.parentNode;
    if (parent === null) {
    assert_throws_dom("INVALID_NODE_TYPE_ERR", function () {
        range.setStartAfter(node);
    }, "setStartAfter() to a node with null parent must throw INVALID_NODE_TYPE_ERR");
    return;
    }

    var idx = 0;
    while (node.parentNode.childNodes[idx] != node) {
    idx++;
    }

    testSetStart(range, node.parentNode, idx + 1);
}

function testSetEndBefore(range, node) {
    var parent = node.parentNode;
    if (parent === null) {
    assert_throws_dom("INVALID_NODE_TYPE_ERR", function () {
        range.setEndBefore(node);
    }, "setEndBefore() to a node with null parent must throw INVALID_NODE_TYPE_ERR");
    return;
    }

    var idx = 0;
    while (node.parentNode.childNodes[idx] != node) {
    idx++;
    }

    testSetEnd(range, node.parentNode, idx);
}

function testSetEndAfter(range, node) {
    var parent = node.parentNode;
    if (parent === null) {
    assert_throws_dom("INVALID_NODE_TYPE_ERR", function () {
        range.setEndAfter(node);
    }, "setEndAfter() to a node with null parent must throw INVALID_NODE_TYPE_ERR");
    return;
    }

    var idx = 0;
    while (node.parentNode.childNodes[idx] != node) {
    idx++;
    }

    testSetEnd(range, node.parentNode, idx + 1);
}


var startTests = [];
var endTests = [];
var startBeforeTests = [];
var startAfterTests = [];
var endBeforeTests = [];
var endAfterTests = [];

// Don't want to eval() each point a bazillion times
var testPointsCached = testPoints.map(eval);
var testNodesCached = testNodesShort.map(eval);

for (var i = 0; i < testRangesShort.length; i++) {
    var endpoints = eval(testRangesShort[i]);
    var range;
    test(function() {
    range = ownerDocument(endpoints[0]).createRange();
    range.setStart(endpoints[0], endpoints[1]);
    range.setEnd(endpoints[2], endpoints[3]);
    }, "Set up range " + i + " " + testRangesShort[i]);

    for (var j = 0; j < testPoints.length; j++) {
    startTests.push(["setStart() with range " + i + " " + testRangesShort[i] + ", point " + j + " " + testPoints[j],
        range,
        testPointsCached[j][0],
        testPointsCached[j][1]
    ]);
    endTests.push(["setEnd() with range " + i + " " + testRangesShort[i] + ", point " + j + " " + testPoints[j],
        range,
        testPointsCached[j][0],
        testPointsCached[j][1]
    ]);
    }

    for (var j = 0; j < testNodesShort.length; j++) {
    startBeforeTests.push(["setStartBefore() with range " + i + " " + testRangesShort[i] + ", node " + j + " " + testNodesShort[j],
        range,
        testNodesCached[j]
    ]);
    startAfterTests.push(["setStartAfter() with range " + i + " " + testRangesShort[i] + ", node " + j + " " + testNodesShort[j],
        range,
        testNodesCached[j]
    ]);
    endBeforeTests.push(["setEndBefore() with range " + i + " " + testRangesShort[i] + ", node " + j + " " + testNodesShort[j],
        range,
        testNodesCached[j]
    ]);
    endAfterTests.push(["setEndAfter() with range " + i + " " + testRangesShort[i] + ", node " + j + " " + testNodesShort[j],
        range,
        testNodesCached[j]
    ]);
    }
}

generate_tests(testSetStart, startTests);
generate_tests(testSetEnd, endTests);
generate_tests(testSetStartBefore, startBeforeTests);
generate_tests(testSetStartAfter, startAfterTests);
generate_tests(testSetEndBefore, endBeforeTests);
generate_tests(testSetEndAfter, endAfterTests);

testDiv.style.display = "none";
</script>
TEST_HTML;
    }
}
