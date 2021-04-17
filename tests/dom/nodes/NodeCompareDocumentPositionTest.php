<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-compareDocumentPosition.html
 */
class NodeCompareDocumentPositionTest extends NodeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider rangeTestNodesProvider
     */
    public function test($referenceName, $otherName)
    {
        $window = self::getWindow();
        $reference = $window->eval($referenceName);
        $other = $window->eval($otherName);

        $result = $reference->compareDocumentPosition($other);

        // "If other and reference are the same object, return zero and
        // terminate these steps."
        if ($other === $reference) {
            $this->assertSame(0, $result);

            return;
        }

        // "If other and reference are not in the same tree, return the
        // result of adding DOCUMENT_POSITION_DISCONNECTED,
        // DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC, and either
        // DOCUMENT_POSITION_PRECEDING or DOCUMENT_POSITION_FOLLOWING, with
        // the constraint that this is to be consistent, together and
        // terminate these steps."
        if ($reference->getRootNode() !== $other->getRootNode()) {
            $this->assertContains($result, [
                Node::DOCUMENT_POSITION_DISCONNECTED +
                Node::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC +
                Node::DOCUMENT_POSITION_PRECEDING,
                Node::DOCUMENT_POSITION_DISCONNECTED +
                Node::DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC +
                Node::DOCUMENT_POSITION_FOLLOWING,
            ]);

            return;
        }

        // "If other is an ancestor of reference, return the result of
        // adding DOCUMENT_POSITION_CONTAINS to DOCUMENT_POSITION_PRECEDING
        // and terminate these steps."
        if ($other->isAncestorOf($reference)) {
            $this->assertSame(
                $result,
                Node::DOCUMENT_POSITION_CONTAINS +
                Node::DOCUMENT_POSITION_PRECEDING
            );

            return;
        }

        // "If other is a descendant of reference, return the result of adding
        // DOCUMENT_POSITION_CONTAINED_BY to DOCUMENT_POSITION_FOLLOWING and
        // terminate these steps."
        if ($other->isDescendantOf($reference)) {
            $this->assertSame(
                $result,
                Node::DOCUMENT_POSITION_CONTAINED_BY +
                Node::DOCUMENT_POSITION_FOLLOWING
            );

            return;
        }

        // "If other is preceding reference return DOCUMENT_POSITION_PRECEDING
        // and terminate these steps."
        $prev = Window::previousNode($reference);

        while ($prev && $prev !== $other) {
            $prev = Window::previousNode($prev);
        }

        if ($prev === $other) {
            $this->assertSame($result, Node::DOCUMENT_POSITION_PRECEDING);

            return;
        }

        // "Return DOCUMENT_POSITION_FOLLOWING."
        $this->assertSame($result, Node::DOCUMENT_POSITION_FOLLOWING);
    }

    public function rangeTestNodesProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        foreach ($window->testNodes as $referenceName) {
            foreach ($window->testNodes as $otherName) {
                yield [$referenceName, $otherName];
            }
        }
    }

    public static function getDocumentName(): string
    {
        return 'Node-compareDocumentPosition.html';
    }
}
