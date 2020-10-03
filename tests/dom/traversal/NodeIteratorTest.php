<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Closure;
use Exception;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\NodeIterator;
use Rowbot\DOM\Tests\dom\Common;
use TypeError;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/NodeIterator.html
 */
class NodeIteratorTest extends TestCase
{
    use Common;

    private static $document;

    public function testDetachIsNoop(): void
    {
        $this->markTestSkipped('NodeIterator::detach() is a no-op and not implemented.');
        $document = $this->loadDocument();
        $iter = $document->createNodeIterator($document);
        $iter->detach();
        $iter->detach();
    }

    public function testCreateNodeIteratorParameterDefaults(): void
    {
        $document = $this->loadDocument();
        $iter = $document->createNodeIterator($document);
        $this->checkIter($iter, $document);
    }

    public function testCreateNodeIteratorWithNullArguments(): void
    {
        $this->expectException(TypeError::class);
        $document = $this->loadDocument();
        $iter = $document->createNodeIterator($document, null, null);
        $this->checkIter($iter, $document);
    }

    public function testPropagateExceptionFromFilterFunction(): void
    {
        $document = $this->loadDocument();
        $iter = $document->createNodeIterator(
            $document,
            NodeFilter::SHOW_ALL,
            static function (): void {
                throw new Exception();
            }
        );
        $this->expectException(Exception::class);
        $iter->nextNode();
    }

    public function testRecursiveFiltersNeedToThrow(): void
    {
        $document = $this->loadDocument();
        $depth = 0;
        $iter = $document->createNodeIterator(
            $document,
            NodeFilter::SHOW_ALL,
            static function () use (&$iter, $document, $depth): int {
                if ($iter->referenceNode !== $document && $depth === 0) {
                    ++$depth;
                    $iter->nextNode();
                }

                return NodeFilter::FILTER_ACCEPT;
            }
        );
        $iter->nextNode();
        $iter->nextNode();

        $this->assertThrows(static function () use ($iter): void {
            $iter->nextNode();
        }, InvalidStateError::class);
        --$depth;
        $this->assertThrows(static function () use ($iter): void {
            $iter->previousNode();
        }, InvalidStateError::class);
    }
    /**
     * @dataProvider iteratorInputProvider
     */
    public function testIterator(Node $root, int $whatToShow, ?Closure $filter): void
    {
        global $document;

        $iter = $document->createNodeIterator($root, $whatToShow, $filter);

        $this->assertSame($root, $iter->root);
        $this->assertSame($root, $iter->referenceNode);
        $this->assertTrue($iter->pointerBeforeReferenceNode);
        $this->assertSame($whatToShow, $iter->whatToShow);
        $this->assertSameFilter($iter, $filter);

        $expectedReferenceNode = $root;
        $expectedBeforeNode = true;
        // "Let node be the value of the referenceNode attribute."
        $node = $root;
        // "Let before node be the value of the pointerBeforeReferenceNode
        // attribute."
        $beforeNode = true;
        $i = 1;

        // Each loop iteration runs nextNode() once.
        while ($node) {
            do {
                if (!$beforeNode) {
                    // "If before node is false, let node be the first node following node
                    // in the iterator collection. If there is no such node return null."
                    $node = self::nextNode($node);

                    if (!$node || !$node->isInclusiveDescendantOf($root)) {
                        $node = null;

                        break;
                    }
                } else {
                    // "If before node is true, set it to false."
                    $beforeNode = false;
                }

                // "Filter node and let result be the return value.
                //
                // "If result is FILTER_ACCEPT, go to the next step in the overall set of
                // steps.
                //
                // "Otherwise, run these substeps again."
                if (
                    !((1 << ($node->nodeType - 1)) & $whatToShow)
                    || $filter && $filter($node) !== NodeFilter::FILTER_ACCEPT
                ) {
                    continue;
                }

                // "Set the referenceNode attribute to node, set the
                // pointerBeforeReferenceNode attribute to before node, and return node."
                $expectedReferenceNode = $node;
                $expectedBeforeNode = $beforeNode;

                break;
            } while (true);

            $this->assertSame($node, $iter->nextNode());
            $this->assertSame($expectedReferenceNode, $iter->referenceNode);
            $this->assertSame($expectedBeforeNode, $iter->pointerBeforeReferenceNode);
            ++$i;
        }

        // Same but for previousNode() (mostly copy-pasted, oh well)
        $iter = $document->createNodeIterator($root, $whatToShow, $filter);

        $expectedReferenceNode = $root;
        $expectedBeforeNode = true;
        // "Let node be the value of the referenceNode attribute."
        $node = $root;
        // "Let before node be the value of the pointerBeforeReferenceNode
        // attribute."
        $beforeNode = true;
        $i = 1;

        // Each loop iteration runs previousNode() once.
        while ($node) {
            do {
                if ($beforeNode) {
                    // "If before node is true, let node be the first node preceding node
                    // in the iterator collection. If there is no such node return null."
                    $node = self::previousNode($node);

                    if (!$node || !$node->isInclusiveDescendantOf($root)) {
                        $node = null;

                        break;
                    }
                } else {
                    // "If before node is true, set it to true."
                    $beforeNode = true;
                }

                // "Filter node and let result be the return value.
                //
                // "If result is FILTER_ACCEPT, go to the next step in the overall set of
                // steps.
                //
                // "Otherwise, run these substeps again."
                if (
                    !((1 << ($node->nodeType - 1)) & $whatToShow)
                    || $filter && $filter($node) !== NodeFilter::FILTER_ACCEPT
                ) {
                    continue;
                }

                // "Set the referenceNode attribute to node, set the
                // pointerBeforeReferenceNode attribute to before node, and return node."
                $expectedReferenceNode = $node;
                $expectedBeforeNode = $beforeNode;

                break;
            } while (true);

            $this->assertSame($node, $iter->previousNode());
            $this->assertSame($expectedReferenceNode, $iter->referenceNode);
            $this->assertSame($expectedBeforeNode, $iter->pointerBeforeReferenceNode);
            ++$i;
        }
    }

    public function checkIter(NodeIterator $iter, Node $root, int $whatToShow = 0xFFFFFFFF): void
    {
        // $this->assertSame('[object NodeIterator]', $iter->toString());
        $this->assertSame($root, $iter->root);
        $this->assertSame($whatToShow, $iter->whatToShow);
        $this->assertNull($iter->filter);
        $this->assertSame($root, $iter->referenceNode);
        $this->assertTrue($iter->pointerBeforeReferenceNode);
        // assert_readonly(iter, 'root');
        // assert_readonly(iter, 'whatToShow');
        // assert_readonly(iter, 'filter');
        // assert_readonly(iter, 'referenceNode');
        // assert_readonly(iter, 'pointerBeforeReferenceNode');
    }

    public function iteratorInputProvider(): array
    {
        global $testNodes, $document;

        $whatToShows = [
            0,
            0xFFFFFFFF,
            NodeFilter::SHOW_ELEMENT,
            NodeFilter::SHOW_ATTRIBUTE,
            NodeFilter::SHOW_ELEMENT | NodeFilter::SHOW_DOCUMENT,
        ];
        $callbacks = [
            null,
            static function (Node $node): int {
                //return true;
                return NodeFilter::FILTER_ACCEPT;
            },
            static function (Node $node): int {
                //return false;
                return NodeFilter::FILTER_REJECT;
            },
            static function (Node $node): int {
                //return $node->nodeName[0] === '#';
                return $node->nodeName[0] === '#'
                    ? NodeFilter::FILTER_ACCEPT
                    : NodeFilter::FILTER_REJECT;
            },
        ];

        $document = self::loadDocument();
        self::setupRangeTests($document);
        $tests = [];

        foreach ($testNodes as $i => $testNode) {
            foreach ($whatToShows as $j => $whatToShow) {
                foreach ($callbacks as $callback) {
                    $tests[] = [$this->eval($testNode, $document), $whatToShow, $callback];
                }
            }
        }

        return $tests;
    }

    public static function loadDocument(): Document
    {
        if (self::$document) {
            return self::$document;
        }

        $html = <<<'TEST_HTML'
<!doctype html>
<title>NodeIterator tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

function check_iter(iter, root, whatToShowValue) {
    whatToShowValue = whatToShowValue === undefined ? 0xFFFFFFFF : whatToShowValue;

    assert_equals(iter.toString(), '[object NodeIterator]', 'toString');
    assert_equals(iter.root, root, 'root');
    assert_equals(iter.whatToShow, whatToShowValue, 'whatToShow');
    assert_equals(iter.filter, null, 'filter');
    assert_equals(iter.referenceNode, root, 'referenceNode');
    assert_equals(iter.pointerBeforeReferenceNode, true, 'pointerBeforeReferenceNode');
    assert_readonly(iter, 'root');
    assert_readonly(iter, 'whatToShow');
    assert_readonly(iter, 'filter');
    assert_readonly(iter, 'referenceNode');
    assert_readonly(iter, 'pointerBeforeReferenceNode');
}

test(function() {
    var iter = document.createNodeIterator(document);
    iter.detach();
    iter.detach();
}, "detach() should be a no-op");

test(function() {
    var iter = document.createNodeIterator(document);
    check_iter(iter, document);
}, "createNodeIterator() parameter defaults");

test(function() {
    var iter = document.createNodeIterator(document, null, null);
    check_iter(iter, document, 0);
}, "createNodeIterator() with null as arguments");

test(function() {
    var iter = document.createNodeIterator(document, undefined, undefined);
    check_iter(iter, document);
}, "createNodeIterator() with undefined as arguments");

test(function() {
    var err = {name: "failed"};
    var iter = document.createNodeIterator(document, NodeFilter.SHOW_ALL,
                                            function() { throw err; });
    assert_throws_exactly(err, function() { iter.nextNode() });
}, "Propagate exception from filter function");

test(function() {
    var depth = 0;
    var iter = document.createNodeIterator(document, NodeFilter.SHOW_ALL,
    function() {
        if (iter.referenceNode != document && depth == 0) {
        depth++;
        iter.nextNode();
        }
        return NodeFilter.FILTER_ACCEPT;
    });
    iter.nextNode();
    iter.nextNode();
    assert_throws_dom("InvalidStateError", function() { iter.nextNode() });
    depth--;
    assert_throws_dom("InvalidStateError", function() { iter.previousNode() });
}, "Recursive filters need to throw");

function testIterator(root, whatToShow, filter) {
    var iter = document.createNodeIterator(root, whatToShow, filter);

    assert_equals(iter.root, root, ".root");
    assert_equals(iter.referenceNode, root, "Initial .referenceNode");
    assert_equals(iter.pointerBeforeReferenceNode, true,
                ".pointerBeforeReferenceNode");
    assert_equals(iter.whatToShow, whatToShow, ".whatToShow");
    assert_equals(iter.filter, filter, ".filter");

    var expectedReferenceNode = root;
    var expectedBeforeNode = true;
    // "Let node be the value of the referenceNode attribute."
    var node = root;
    // "Let before node be the value of the pointerBeforeReferenceNode
    // attribute."
    var beforeNode = true;
    var i = 1;
    // Each loop iteration runs nextNode() once.
    while (node) {
    do {
        if (!beforeNode) {
        // "If before node is false, let node be the first node following node
        // in the iterator collection. If there is no such node return null."
        node = nextNode(node);
        if (!isInclusiveDescendant(node, root)) {
            node = null;
            break;
        }
        } else {
        // "If before node is true, set it to false."
        beforeNode = false;
        }
        // "Filter node and let result be the return value.
        //
        // "If result is FILTER_ACCEPT, go to the next step in the overall set of
        // steps.
        //
        // "Otherwise, run these substeps again."
        if (!((1 << (node.nodeType - 1)) & whatToShow)
            || (filter && filter(node) != NodeFilter.FILTER_ACCEPT)) {
        continue;
        }

        // "Set the referenceNode attribute to node, set the
        // pointerBeforeReferenceNode attribute to before node, and return node."
        expectedReferenceNode = node;
        expectedBeforeNode = beforeNode;

        break;
    } while (true);

    assert_equals(iter.nextNode(), node, ".nextNode() " + i + " time(s)");
    assert_equals(iter.referenceNode, expectedReferenceNode,
                    ".referenceNode after nextNode() " + i + " time(s)");
    assert_equals(iter.pointerBeforeReferenceNode, expectedBeforeNode,
                ".pointerBeforeReferenceNode after nextNode() " + i + " time(s)");

    i++;
    }

    // Same but for previousNode() (mostly copy-pasted, oh well)
    var iter = document.createNodeIterator(root, whatToShow, filter);

    var expectedReferenceNode = root;
    var expectedBeforeNode = true;
    // "Let node be the value of the referenceNode attribute."
    var node = root;
    // "Let before node be the value of the pointerBeforeReferenceNode
    // attribute."
    var beforeNode = true;
    var i = 1;
    // Each loop iteration runs previousNode() once.
    while (node) {
    do {
        if (beforeNode) {
        // "If before node is true, let node be the first node preceding node
        // in the iterator collection. If there is no such node return null."
        node = previousNode(node);
        if (!isInclusiveDescendant(node, root)) {
            node = null;
            break;
        }
        } else {
        // "If before node is false, set it to true."
        beforeNode = true;
        }
        // "Filter node and let result be the return value.
        //
        // "If result is FILTER_ACCEPT, go to the next step in the overall set of
        // steps.
        //
        // "Otherwise, run these substeps again."
        if (!((1 << (node.nodeType - 1)) & whatToShow)
            || (filter && filter(node) != NodeFilter.FILTER_ACCEPT)) {
        continue;
        }

        // "Set the referenceNode attribute to node, set the
        // pointerBeforeReferenceNode attribute to before node, and return node."
        expectedReferenceNode = node;
        expectedBeforeNode = beforeNode;

        break;
    } while (true);

    assert_equals(iter.previousNode(), node, ".previousNode() " + i + " time(s)");
    assert_equals(iter.referenceNode, expectedReferenceNode,
                    ".referenceNode after previousNode() " + i + " time(s)");
    assert_equals(iter.pointerBeforeReferenceNode, expectedBeforeNode,
            ".pointerBeforeReferenceNode after previousNode() " + i + " time(s)");

    i++;
    }
}

var whatToShows = [
    "0",
    "0xFFFFFFFF",
    "NodeFilter.SHOW_ELEMENT",
    "NodeFilter.SHOW_ATTRIBUTE",
    "NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_DOCUMENT",
];

var callbacks = [
    "null",
    "(function(node) { return true })",
    "(function(node) { return false })",
    "(function(node) { return node.nodeName[0] == '#' })",
];

for (var i = 0; i < testNodes.length; i++) {
    for (var j = 0; j < whatToShows.length; j++) {
    for (var k = 0; k < callbacks.length; k++) {
        test(() => {
        testIterator(eval(testNodes[i]), eval(whatToShows[j]), eval(callbacks[k]));
        }, "document.createNodeIterator(" + testNodes[i] + ", " + whatToShows[j] + ", " + callbacks[k] + ")");
    }
    }
}

testDiv.style.display = "none";
</script>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');

        return self::$document;
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
