<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Generator;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function count;
use function sprintf;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/NodeIterator-removal.html
 */
class NodeIteratorRemovalTest extends TestCase
{
    use WindowTrait;

    /**
     * @dataProvider nodesProvider
     */
    public function testRemovingNode(Node $node): void
    {
        $window = self::getWindow();
        $document = $window->document;
        $iters = [];
        $descs = [];
        $expectedReferenceNodes = [];
        $expectedPointers = [];

        foreach ($window->testNodes as $j => $root) {
            $root = $window->eval($root);

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
                        $window->testNodes[$j],
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
                    if (
                        $node->isInclusiveAncestorOf($root)
                        || !$node->isInclusiveAncestorOf($iter->referenceNode)
                    ) {
                        continue;
                    }

                    // "If the pointerBeforeReferenceNode attribute value is false, set
                    // the referenceNode attribute to the first node preceding the node
                    // that is being removed, and terminate these steps."
                    if (!$iter->pointerBeforeReferenceNode) {
                        $expectedReferenceNodes[$idx] = Window::previousNode($node);
                        continue;
                    }

                    // "If there is a node following the last inclusive descendant of the
                    // node that is being removed, set the referenceNode attribute to the
                    // first such node, and terminate these steps."
                    $next = Window::nextNodeDescendants($node);

                    if ($next) {
                        $expectedReferenceNodes[$idx] = $next;
                        continue;
                    }

                    // "Set the referenceNode attribute to the first node preceding the
                    // node that is being removed and set the pointerBeforeReferenceNode
                    // attribute to false."
                    $expectedReferenceNodes[$idx] = Window::previousNode($node);
                    $expectedPointers[$idx] = false;
                }
            }
        }

        $oldParent = $node->parentNode;
        $oldSibling = $node->nextSibling;
        $oldParent->removeChild($node);
        $actualReferenceNodes = [];
        $actualPointers = [];

        foreach ($iters as $iter) {
            $actualReferenceNodes[] = $iter->referenceNode;
            $actualPointers[] = $iter->pointerBeforeReferenceNode;
        }

        $oldParent->insertBefore($node, $oldSibling);

        // Do assertions afterwards so that a failing assertion doesn't prevent us from restoring
        // the node back to its original position in the tree.
        for ($j = 0, $length = count($iters); $j < $length; $j++) {
            $this->assertSame(
                $expectedReferenceNodes[$j],
                $actualReferenceNodes[$j],
                sprintf('.referenceNode of %s', $descs[$j])
            );
            $this->assertSame(
                $expectedPointers[$j],
                $actualPointers[$j],
                sprintf('.pointerBeforeReferenceNode of %s', $descs[$j])
            );
        }
    }

    public function nodesProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        foreach ($window->testNodes as $nodeString) {
            $node = $window->eval($nodeString);

            if (!$node->parentNode) {
                // Nothing to test
                continue;
            }

            yield [$node];
        }
    }

    public static function getDocumentName(): string
    {
        return 'NodeIterator-removal.html';
    }
}
