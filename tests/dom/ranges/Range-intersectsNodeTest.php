<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Generator;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-intersectsNode.html
 */
class RangeIntersectsNodeTest extends TestCase
{
    use Common;

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
            $this->getPosition($parent_, $offset, $range->endContainer, $range->endOffset) === 'before'
            && $this->getPosition($parent_, $offset + 1, $range->startContainer, $range->startOffset) === 'after'
        ) {
            $this->assertTrue($range->intersectsNode($node));

            return;
        }

        $this->assertFalse($range->intersectsNode($node));
    }

    public function intersectionNodeProvider(): Generator
    {
        global $testNodes, $testRanges;

        $document = self::loadDocument();
        self::setupRangeTests($document);

        foreach ($testNodes as $node) {
            $node = $this->eval($node, $document);

            foreach ($testRanges as $range) {
                try {
                    $range = $this->rangeFromEndpoints($this->eval($range, $document));
                } catch (Exception $e) {
                    $range = null;
                }

                yield [$node, $range];
            }
        }
    }

    public static function loadDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.intersectsNode() tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

// Will be filled in on the first run for that range
var testRangesCached = [];

for (var i = 0; i < testNodes.length; i++) {
    var node = eval(testNodes[i]);

    for (var j = 0; j < testRanges.length; j++) {
    test(function() {
        if (testRangesCached[j] === undefined) {
        try {
            testRangesCached[j] = rangeFromEndpoints(eval(testRanges[i]));
        } catch(e) {
            testRangesCached[j] = null;
        }
        }
        assert_not_equals(testRangesCached[j], null,
        "Setting up the range failed");

        var range = testRangesCached[j].cloneRange();

        // "If node's root is different from the context object's root,
        // return false and terminate these steps."
        if (furthestAncestor(node) !== furthestAncestor(range.startContainer)) {
        assert_equals(range.intersectsNode(node), false,
            "Must return false if node and range have different roots");
        return;
        }

        // "Let parent be node's parent."
        var parent_ = node.parentNode;

        // "If parent is null, return true and terminate these steps."
        if (!parent_) {
        assert_equals(range.intersectsNode(node), true,
            "Must return true if node's parent is null");
        return;
        }

        // "Let offset be node's index."
        var offset = indexOf(node);

        // "If (parent, offset) is before end and (parent, offset + 1) is
        // after start, return true and terminate these steps."
        if (getPosition(parent_, offset, range.endContainer, range.endOffset) === "before"
        && getPosition(parent_, offset + 1, range.startContainer, range.startOffset) === "after") {
        assert_equals(range.intersectsNode(node), true,
            "Must return true if (parent, offset) is before range end and (parent, offset + 1) is after range start");
        return;
        }

        // "Return false."
        assert_equals(range.intersectsNode(node), false,
        "Must return false if (parent, offset) is not before range end or (parent, offset + 1) is not after range start");
    }, "Node " + i + " " + testNodes[i] + ", range " + j + " " + testRanges[j]);
    }
}

testDiv.style.display = "none";
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
