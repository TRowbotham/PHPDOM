<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

use function pow;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-isPointInRange.html
 */
class RangeIsPointInRangeTest extends TestCase
{
    use Common;

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
                $this->getPosition($node, $normalizedOffset, $range->startContainer, $range->startOffset) === 'before'
                || $this->getPosition($node, $normalizedOffset, $range->endContainer, $range->endOffset) === 'after'
            ) {
                $this->assertFalse($range->isPointInRange($node, $offset));

                return;
            }

            $this->assertTrue($range->isPointInRange($node, $offset));
        }
    }

    public function pointsProvider(): Generator
    {
        global $testRanges, $testPoints;

        $document = self::loadDocument();
        self::setupRangeTests($document);

        if (!self::$testRangesCached) {
            self::$testRangesCached = [];

            foreach ($testRanges as $range) {
                self::$testRangesCached[] = $this->rangeFromEndpoints($this->eval($range, $document));
            }
        }

        $detachedRange = $document->createRange();
        $detachedRange->detach();
        $testRanges[] = 'detached';
        $testRangesCached[] = $detachedRange;

        foreach ($testPoints as $testPoint) {
            $evaled = $this->eval($testPoint, $document);
            yield [$evaled[0], $evaled[1]];
        }
    }

    public static function loadDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.isPointInRange() tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

var testRangesCached = [];
test(function() {
    for (var j = 0; j < testRanges.length; j++) {
    test(function() {
        testRangesCached[j] = rangeFromEndpoints(eval(testRanges[j]));
    }, "Set up for range " + j + " " + testRanges[j]);
    }
    var detachedRange = document.createRange();
    detachedRange.detach();
    testRanges.push("detached");
    testRangesCached.push(detachedRange);
}, "Setup");

for (var i = 0; i < testPoints.length; i++) {
    var node = eval(testPoints[i])[0];
    var offset = eval(testPoints[i])[1];

    // isPointInRange is an unsigned long, so per WebIDL, we need to treat it
    // as though it wrapped to an unsigned 32-bit integer.
    var normalizedOffset = offset % Math.pow(2, 32);
    if (normalizedOffset < 0) {
    normalizedOffset += Math.pow(2, 32);
    }

    for (var j = 0; j < testRanges.length; j++) {
    test(function() {
        var range = testRangesCached[j].cloneRange();

        // "If node's root is different from the context object's root,
        // return false and terminate these steps."
        if (furthestAncestor(node) !== furthestAncestor(range.startContainer)) {
        assert_false(range.isPointInRange(node, offset),
            "Must return false if node has a different root from the context object");
        return;
        }

        // "If node is a doctype, throw an "InvalidNodeTypeError" exception
        // and terminate these steps."
        if (node.nodeType == Node.DOCUMENT_TYPE_NODE) {
        assert_throws_dom("INVALID_NODE_TYPE_ERR", function() {
            range.isPointInRange(node, offset);
        }, "Must throw InvalidNodeTypeError if node is a doctype");
        return;
        }

        // "If offset is greater than node's length, throw an
        // "IndexSizeError" exception and terminate these steps."
        if (normalizedOffset > nodeLength(node)) {
        assert_throws_dom("INDEX_SIZE_ERR", function() {
            range.isPointInRange(node, offset);
        }, "Must throw IndexSizeError if offset is greater than  length");
        return;
        }

        // "If (node, offset) is before start or after end, return false
        // and terminate these steps."
        if (getPosition(node, normalizedOffset, range.startContainer, range.startOffset) === "before"
        || getPosition(node, normalizedOffset, range.endContainer, range.endOffset) === "after") {
        assert_false(range.isPointInRange(node, offset),
            "Must return false if point is before start or after end");
        return;
        }

        // "Return true."
        assert_true(range.isPointInRange(node, offset),
        "Must return true if point is not before start, after end, or in different tree");
    }, "Point " + i + " " + testPoints[i] + ", range " + j + " " + testRanges[j]);
    }
}

testDiv.style.display = "none";
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }

    public static function tearDownAfterClass(): void
    {
        self::$testRangesCached = null;
    }
}
