<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-compareDocumentPosition.html
 */
class NodeCompareDocumentPositionTest extends TestCase
{
    use Common;
    use DocumentGetter;

    public function rangeTestNodesProvider()
    {
        $document = $this->getHTMLDocument();
        self::setupRangeTests($document);

        return $this->getTestNodes($document);
    }

    /**
     * @dataProvider rangeTestNodesProvider
     */
    public function test($referenceName, $otherName)
    {
        $document = $this->getHTMLDocument();
        $reference = $this->eval($referenceName, $document);
        $other = $this->eval($otherName, $document);

        $result = $reference->compareDocumentPosition($other);

        // "If other and reference are the same object, return zero and
        // terminate these steps."
        if ($other === $reference) {
            $this->assertEquals(0, $result);
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
                Node::DOCUMENT_POSITION_FOLLOWING
            ]);
            return;
        }

        // "If other is an ancestor of reference, return the result of
        // adding DOCUMENT_POSITION_CONTAINS to DOCUMENT_POSITION_PRECEDING
        // and terminate these steps."
        if ($other->isAncestorOf($reference)) {
            $this->assertEquals(
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
            $this->assertEquals(
                $result,
                Node::DOCUMENT_POSITION_CONTAINED_BY +
                Node::DOCUMENT_POSITION_FOLLOWING
            );
            return;
        }

        // "If other is preceding reference return DOCUMENT_POSITION_PRECEDING
        // and terminate these steps."
        $prev = self::previousNode($reference);

        while ($prev && $prev !== $other) {
            $prev = self::previousNode($prev);
        }

        if ($prev === $other) {
            $this->assertEquals($result, Node::DOCUMENT_POSITION_PRECEDING);
            return;
        }

        // "Return DOCUMENT_POSITION_FOLLOWING."
        $this->assertEquals($result, Node::DOCUMENT_POSITION_FOLLOWING);
    }
}
