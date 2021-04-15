<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\traversal;

use Closure;
use Exception;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\NodeIterator;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Throwable;
use TypeError;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/NodeIterator.html
 */
class NodeIteratorTest extends TestCase
{
    use WindowTrait;

    public function testDetachIsNoop(): void
    {
        $this->markTestSkipped('NodeIterator::detach() is a no-op and not implemented.');
        $document = self::getWindow()->document;
        $iter = $document->createNodeIterator($document);
        $iter->detach();
        $iter->detach();
    }

    public function testCreateNodeIteratorParameterDefaults(): void
    {
        $document = self::getWindow()->document;
        $iter = $document->createNodeIterator($document);
        $this->checkIter($iter, $document);
    }

    public function testCreateNodeIteratorWithNullArguments(): void
    {
        $this->expectException(TypeError::class);
        $document = self::getWindow()->document;
        $iter = $document->createNodeIterator($document, null, null);
        $this->checkIter($iter, $document);
    }

    public function testPropagateExceptionFromFilterFunction(): void
    {
        $document = self::getWindow()->document;
        $iter = $document->createNodeIterator(
            $document,
            NodeFilter::SHOW_ALL,
            static function (): void {
                throw new Exception();
            }
        );
        $this->expectException(Throwable::class);
        $iter->nextNode();
    }

    public function testRecursiveFiltersNeedToThrow(): void
    {
        $document = self::getWindow()->document;
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
        $document = self::getWindow()->document;
        $iter = $document->createNodeIterator($root, $whatToShow, $filter);

        $this->assertSame($root, $iter->root);
        $this->assertSame($root, $iter->referenceNode);
        $this->assertTrue($iter->pointerBeforeReferenceNode);
        $this->assertSame($whatToShow, $iter->whatToShow);
        $this->assertSame($filter, $iter->filter);

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
                    $node = Window::nextNode($node);

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
                    $node = Window::previousNode($node);

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

        $window = self::getWindow();
        $window->setupRangeTests();
        $tests = [];

        foreach ($window->testNodes as $i => $testNode) {
            foreach ($whatToShows as $j => $whatToShow) {
                foreach ($callbacks as $callback) {
                    $tests[] = [$window->eval($testNode), $whatToShow, $callback];
                }
            }
        }

        return $tests;
    }

    public static function getDocumentName(): string
    {
        return 'NodeIterator.html';
    }
}
