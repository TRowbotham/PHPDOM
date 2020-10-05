<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

use function count;
use function sprintf;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/NodeIterator-removal.html
 */
class NodeIteratorRemovalTest extends TestCase
{
    use Common;

    /**
     * @dataProvider nodesProvider
     */
    public function testRemovingNode(Node $node): void
    {
        global $testNodes, $document;

        $iters = [];
        $descs = [];
        $expectedReferenceNodes = [];
        $expectedPointers = [];

        foreach ($testNodes as $j => $root) {
            $root = $this->eval($root, $document);

            // Add all distinct iterators with this root, calling nextNode()
            // repeatedly until it winds up with the same iterator.
            for ($k = 0;; $k++) {
                $iter = $document->createNodeIterator($root);

                for ($l = 0; $l < $k; $l++) {
                    $iter->nextNode();
                }

                if (
                    $k
                    && $iter->referenceNode === $iters[count($iters) - 1]->referenceNode
                    && $iter->pointerBeforeReferenceNode === $iters[count($iters) - 1]->pointerBeforeReferenceNode
                ) {
                    break;
                } else {
                    $iters[] = $iter;
                    $descs[] = sprintf(
                        "document.createNodeIterator(%s), advanced %d times.",
                        $testNodes[$j],
                        $k
                    );
                    $expectedReferenceNodes[] = $iter->referenceNode;
                    $expectedPointers[] = $iter->pointerBeforeReferenceNode;

                    $idx = count($iters) - 1;

                    // "If the node is root or is not an inclusive ancestor of the
                    // referenceNode attribute value, terminate these steps."
                    //
                    // We also have to rule out the case where node is an ancestor of
                    // root, which is implicitly handled by the spec since such a node
                    // was not part of the iterator collection to start with.
                    if ($node->isInclusiveAncestorOf($root) ||
                        !$node->isInclusiveAncestorOf($iter->referenceNode)
                    ) {
                        continue;
                    }

                    // "If the pointerBeforeReferenceNode attribute value is false, set
                    // the referenceNode attribute to the first node preceding the node
                    // that is being removed, and terminate these steps."
                    if (!$iter->pointerBeforeReferenceNode) {
                        $expectedReferenceNodes[$idx] = $this->previousNode($node);
                        continue;
                    }

                    // "If there is a node following the last inclusive descendant of the
                    // node that is being removed, set the referenceNode attribute to the
                    // first such node, and terminate these steps."
                    $next = self::nextNodeDescendants($node);

                    if ($next) {
                        $expectedReferenceNodes[$idx] = $next;
                        continue;
                    }

                    // "Set the referenceNode attribute to the first node preceding the
                    // node that is being removed and set the pointerBeforeReferenceNode
                    // attribute to false."
                    $expectedReferenceNodes[$idx] = $this->previousNode($node);
                    $expectedPointers[$idx] = false;
                }
            }
        }

        $oldParent = $node->parentNode;
        $oldSibling = $node->nextSibling;
        $oldParent->removeChild($node);

        for ($j = 0, $length = count($iters); $j < $length; $j++) {
            $iter = $iters[$j];
            $this->assertSame(
                $expectedReferenceNodes[$j],
                $iter->referenceNode,
                sprintf('.referenceNode of %s', $descs[$j])
            );
            $this->assertSame(
                $expectedPointers[$j],
                $iter->pointerBeforeReferenceNode,
                sprintf('.pointerBeforeReferenceNode of %s', $descs[$j])
            );
        }

        $oldParent->insertBefore($node, $oldSibling);
    }

    public function nodesProvider(): array
    {
        global $testNodes, $document;

        $document = $this->loadDocument();
        self::setupRangeTests($document);
        $tests = [];

        foreach ($testNodes as $nodeString) {
            $node = $this->eval($nodeString, $document);

            if (!$node->parentNode) {
                // Nothing to test
                continue;
            }

            $tests[] = [$node];
        }

        return $tests;
    }

    public static function loadDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>NodeIterator removal tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

for (var i = 0; i < testNodes.length; i++) {
    var node = eval(testNodes[i]);
    if (!node.parentNode) {
    // Nothing to test
    continue;
    }
    test(function() {
    var iters = [];
    var descs = [];
    var expectedReferenceNodes = [];
    var expectedPointers = [];

    for (var j = 0; j < testNodes.length; j++) {
        var root = eval(testNodes[j]);
        // Add all distinct iterators with this root, calling nextNode()
        // repeatedly until it winds up with the same iterator.
        for (var k = 0; ; k++) {
        var iter = document.createNodeIterator(root);
        for (var l = 0; l < k; l++) {
            iter.nextNode();
        }
        if (k && iter.referenceNode == iters[iters.length - 1].referenceNode
            && iter.pointerBeforeReferenceNode
                == iters[iters.length - 1].pointerBeforeReferenceNode) {
            break;
        } else {
            iters.push(iter);
            descs.push("document.createNodeIterator(" + testNodes[j]
            + ") advanced " + k + " times");
            expectedReferenceNodes.push(iter.referenceNode);
            expectedPointers.push(iter.pointerBeforeReferenceNode);

            var idx = iters.length - 1;

            // "If the node is root or is not an inclusive ancestor of the
            // referenceNode attribute value, terminate these steps."
            //
            // We also have to rule out the case where node is an ancestor of
            // root, which is implicitly handled by the spec since such a node
            // was not part of the iterator collection to start with.
            if (isInclusiveAncestor(node, root)
                || !isInclusiveAncestor(node, iter.referenceNode)) {
            continue;
            }

            // "If the pointerBeforeReferenceNode attribute value is false, set
            // the referenceNode attribute to the first node preceding the node
            // that is being removed, and terminate these steps."
            if (!iter.pointerBeforeReferenceNode) {
            expectedReferenceNodes[idx] = previousNode(node);
            continue;
            }

            // "If there is a node following the last inclusive descendant of the
            // node that is being removed, set the referenceNode attribute to the
            // first such node, and terminate these steps."
            var next = nextNodeDescendants(node);
            if (next) {
            expectedReferenceNodes[idx] = next;
            continue;
            }

            // "Set the referenceNode attribute to the first node preceding the
            // node that is being removed and set the pointerBeforeReferenceNode
            // attribute to false."
            expectedReferenceNodes[idx] = previousNode(node);
            expectedPointers[idx] = false;
        }
        }
    }

    var oldParent = node.parentNode;
    var oldSibling = node.nextSibling;
    oldParent.removeChild(node);

    for (var j = 0; j < iters.length; j++) {
        var iter = iters[j];
        assert_equals(iter.referenceNode, expectedReferenceNodes[j],
                    ".referenceNode of " + descs[j]);
        assert_equals(iter.pointerBeforeReferenceNode, expectedPointers[j],
                    ".pointerBeforeReferenceNode of " + descs[j]);
    }

    oldParent.insertBefore(node, oldSibling);
    }, "Test removing node " + testNodes[i]);
}

testDiv.style.display = "none";
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
