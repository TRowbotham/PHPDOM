<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-selectNode.html
 */
class RangeSelectNodeTest extends RangeTestCase
{
    private $tests;

    /**
     * @dataProvider rangeProvider
     */
    public function testSelectNode(string $marker, Range $range, Node $node): void
    {
        try {
            $range->collapsed;
        } catch (Exception $e) {
            // Range is detached
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNode($node);
            }, InvalidStateError::class);
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNodeContents($node);
            }, InvalidStateError::class);

            return;
        }

        if (!$node->parentNode) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNode($node);
            }, InvalidNodeTypeError::class);
        } else {
            $index = 0;

            while ($node->parentNode->childNodes[$index] !== $node) {
                ++$index;
            }

            $range->selectNode($node);

            $this->assertSame($node->parentNode, $range->startContainer);
            $this->assertSame($node->parentNode, $range->endContainer);
            $this->assertSame($index, $range->startOffset);
            $this->assertSame($index + 1, $range->endOffset);
        }

        if ($node->nodeType === Node::DOCUMENT_TYPE_NODE) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNodeContents($node);
            }, InvalidNodeTypeError::class);
        } else {
            $range->selectNodeContents($node);

            $this->assertSame($node, $range->startContainer);
            $this->assertSame($node, $range->endContainer);
            $this->assertSame(0, $range->startOffset);
            $this->assertSame($node->getLength(), $range->endOffset);
        }
    }

    public function rangeProvider(): array
    {
        global $foreignDoc, $detachedDiv, $range, $xmlDoc, $foreignRange, $xmlRange, $detachedRange;

        self::setUpBeforeClass();
        $range = self::$document->createRange();
        $foreignRange = $foreignDoc->createRange();
        $xmlRange = $xmlDoc->createRange();
        $detachedRange = self::$document->createRange();
        $detachedRange->detach();
        $this->tests = [];

        $this->generateTestTree(self::$document, 'current doc');
        $this->generateTestTree($foreignDoc, 'foreign doc');
        $this->generateTestTree($detachedDiv, 'detached div in current doc');

        $otherTests = ['$xmlDoc', '$xmlElement', '$detachedTextNode',
        '$foreignTextNode', '$xmlTextNode', '$processingInstruction', '$comment',
        '$foreignComment', '$xmlComment', '$docfrag', '$foreignDocfrag', '$xmlDocfrag'];

        foreach ($otherTests as $test) {
            $this->generateTestTree($this->eval($test, self::$document), $test);
        }

        return $this->tests;
    }

    public function generateTestTree(Node $root, string $marker): void
    {
        global $range, $foreignRange, $xmlRange, $detachedRange;

        if ($root->nodeType === Node::ELEMENT_NODE && $root->id === 'log') {
            // This is being modified during the tests, so let's not test it.
            return;
        }

        $this->tests[] = [$marker, $range, $root];
        $this->tests[] = [$marker, $foreignRange, $root];
        $this->tests[] = [$marker, $xmlRange, $root];
        $this->tests[] = [$marker, $detachedRange, $root];

        foreach ($root->childNodes as $node) {
            $this->generateTestTree($node, $marker);
        }
    }

    public static function fetchDocument(): string
    {
        return <<<'TEST_HTML'
<!doctype html>
<title>Range.selectNode() and .selectNodeContents() tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

function testSelectNode(range, node) {
    try {
        range.collapsed;
    } catch (e) {
        // Range is detached
        assert_throws_dom("INVALID_STATE_ERR", function () {
            range.selectNode(node);
        }, "selectNode() on a detached node must throw INVALID_STATE_ERR");
        assert_throws_dom("INVALID_STATE_ERR", function () {
            range.selectNodeContents(node);
        }, "selectNodeContents() on a detached node must throw INVALID_STATE_ERR");
        return;
    }

    if (!node.parentNode) {
        assert_throws_dom("INVALID_NODE_TYPE_ERR", function() {
            range.selectNode(node);
        }, "selectNode() on a node with no parent must throw INVALID_NODE_TYPE_ERR");
    } else {
        var index = 0;
        while (node.parentNode.childNodes[index] != node) {
            index++;
        }

        range.selectNode(node);
        assert_equals(range.startContainer, node.parentNode,
            "After selectNode(), startContainer must equal parent node");
        assert_equals(range.endContainer, node.parentNode,
            "After selectNode(), endContainer must equal parent node");
        assert_equals(range.startOffset, index,
            "After selectNode(), startOffset must be index of node in parent (" + index + ")");
        assert_equals(range.endOffset, index + 1,
            "After selectNode(), endOffset must be one plus index of node in parent (" + (index + 1) + ")");
    }

    if (node.nodeType == Node.DOCUMENT_TYPE_NODE) {
        assert_throws_dom("INVALID_NODE_TYPE_ERR", function () {
            range.selectNodeContents(node);
        }, "selectNodeContents() on a doctype must throw INVALID_NODE_TYPE_ERR");
    } else {
        range.selectNodeContents(node);
        assert_equals(range.startContainer, node,
            "After selectNodeContents(), startContainer must equal node");
        assert_equals(range.endContainer, node,
            "After selectNodeContents(), endContainer must equal node");
        assert_equals(range.startOffset, 0,
            "After selectNodeContents(), startOffset must equal 0");
        var len = nodeLength(node);
        assert_equals(range.endOffset, len,
            "After selectNodeContents(), endOffset must equal node length (" + len + ")");
    }
}

var range = document.createRange();
var foreignRange = foreignDoc.createRange();
var xmlRange = xmlDoc.createRange();
var detachedRange = document.createRange();
detachedRange.detach();
var tests = [];
function testTree(root, marker) {
    if (root.nodeType == Node.ELEMENT_NODE && root.id == "log") {
        // This is being modified during the tests, so let's not test it.
        return;
    }
    tests.push([marker + ": " + root.nodeName.toLowerCase() + " node, current doc's range, type " + root.nodeType, range, root]);
    tests.push([marker + ": " + root.nodeName.toLowerCase() + " node, foreign doc's range, type " + root.nodeType, foreignRange, root]);
    tests.push([marker + ": " + root.nodeName.toLowerCase() + " node, XML doc's range, type " + root.nodeType, xmlRange, root]);
    tests.push([marker + ": " + root.nodeName.toLowerCase() + " node, detached range, type " + root.nodeType, detachedRange, root]);
    for (var i = 0; i < root.childNodes.length; i++) {
        testTree(root.childNodes[i], marker + "[" + i + "]");
    }
}
testTree(document, "current doc");
testTree(foreignDoc, "foreign doc");
testTree(detachedDiv, "detached div in current doc");

var otherTests = ["xmlDoc", "xmlElement", "detachedTextNode",
"foreignTextNode", "xmlTextNode", "processingInstruction", "comment",
"foreignComment", "xmlComment", "docfrag", "foreignDocfrag", "xmlDocfrag"];

for (var i = 0; i < otherTests.length; i++) {
    testTree(window[otherTests[i]], otherTests[i]);
}

generate_tests(testSelectNode, tests);

testDiv.style.display = "none";
</script>
TEST_HTML;
    }
}
