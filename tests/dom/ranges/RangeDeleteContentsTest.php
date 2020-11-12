<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function count;
use function substr;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-deleteContents.html
 */
class RangeDeleteContentsTest extends RangeTestCase
{
    use WindowTrait;

    private static $actualIframe;
    private static $expectedIframe;
    private static $referenceDoc;

    /**
     * @dataProvider rangesProvider
     */
    public function testDeleteContents(int $i): void
    {
        self::restoreIframe(self::$actualIframe, $i);
        self::restoreIframe(self::$expectedIframe, $i);

        $actualRange = self::$expectedIframe->contentWindow->testRange;
        $expectedRange = self::$expectedIframe->contentWindow->testRange;

        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);

        $actualAllNodes = [];
        $node = Window::furthestAncestor($actualRange->startContainer);

        do {
            $actualAllNodes[] = $node;
        } while ($node = Window::nextNode($node));

        $expectedAllNodes = [];
        $node = Window::furthestAncestor($expectedRange->startContainer);

        do {
            $expectedAllNodes[] = $node;
        } while ($node = Window::nextNode($node));

        $actualRange->deleteContents();
        self::myDeleteContents($expectedRange);

        $actualRoots = [];

        for ($j = 0, $length = count($actualAllNodes); $j < $length; ++$j) {
            if (!$actualAllNodes[$j]->parentNode) {
                $actualRoots[] = $actualAllNodes[$j];
            }
        }

        $expectedRoots = [];

        for ($j = 0, $length = count($expectedAllNodes); $j < $length; ++$j) {
            if (!$expectedAllNodes[$j]->parentNode) {
                $expectedRoots[] = $expectedAllNodes[$j];
            }
        }

        for ($j = 0, $length = count($actualRoots); $j < $length; ++$j) {
            $this->assertTrue($actualRoots[$j]->isEqualNode($expectedRoots[$j]));
        }

        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);
        $this->assertTrue($actualRoots[0]->isEqualNode($expectedRoots[0]));

        $this->assertSame($actualRange->startContainer, $actualRange->endContainer);
        $this->assertSame($actualRange->startOffset, $actualRange->endOffset);
        $this->assertSame($expectedRange->startContainer, $expectedRange->endContainer);
        $this->assertSame($expectedRange->startOffset, $expectedRange->endOffset);

        $this->assertSame($actualRange->startOffset, $expectedRange->startOffset);
        $this->assertTrue($actualRange->startContainer->isEqualNode($expectedRange->startContainer));

        $currentActual = $actualRange->startContainer;
        $currentExpected = $expectedRange->startContainer;
        $actual = '';
        $expected = '';

        while ($currentActual && $currentExpected) {
            $actual = Window::indexOf($currentActual) . '-' . $actual;
            $expected = Window::indexOf($currentExpected) . '-' . $expected;

            $currentActual = $currentActual->parentNode;
            $currentExpected = $currentExpected->parentNode;
        }

        $actual = substr($actual, 0, -1);
        $expected = substr($expected, 0, -1);
        $this->assertSame($expected, $actual);
    }

    public function rangesProvider()
    {
        $window = self::getWindow();
        $window->initStrings();

        foreach ($window->testRanges as $i => $_) {
            yield [$i];
        }
    }

    public static function setUpBeforeClass(): void
    {
        $window = self::getWindow();
        $document = $window->document;
        $window->setupRangeTests();
        $window->testDiv->parentNode->removeChild($window->testDiv);

        $file = __DIR__ . DS . 'html' . DS . 'Range-test-iframe.html';
        self::$actualIframe = FakeIframe::load($file);
        self::$expectedIframe = FakeIframe::load($file);

        self::$referenceDoc = $document->implementation->createHTMLDocument('');
        self::$referenceDoc->removeChild(self::$referenceDoc->documentElement);
        self::$referenceDoc->appendChild(self::$actualIframe->contentDocument->documentElement->cloneNode(true));

        self::registerCleanup(static function (): void {
            self::$actualIframe = null;
            self::$expectedIframe = null;
            self::$referenceDoc = null;
        });
    }

    public static function myDeleteContents(Range $range): void
    {
        // "If the context object's start and end are the same, abort this method."
        if (
            $range->startContainer === $range->endContainer
            && $range->startOffset === $range->endOffset
        ) {
            return;
        }

        // "Let original start node, original start offset, original end node, and
        // original end offset be the context object's start and end nodes and
        // offsets, respectively."
        $originalStartNode = $range->startContainer;
        $originalStartOffset = $range->startOffset;
        $originalEndNode = $range->endContainer;
        $originalEndOffset = $range->endOffset;

        // "If original start node and original end node are the same, and they are
        // a Text, ProcessingInstruction, or Comment node, replace data with node
        // original start node, offset original start offset, count original end
        // offset minus original start offset, and data the empty string, and then
        // terminate these steps"
        if (
            $originalStartNode === $originalEndNode
            && ($range->startContainer->nodeType === Node::TEXT_NODE
                || $range->startContainer->nodeType === Node::PROCESSING_INSTRUCTION_NODE
                || $range->startContainer->nodeType === Node::COMMENT_NODE)
        ) {
            $originalStartNode->deleteData($originalStartOffset, $originalEndOffset - $originalStartOffset);

            return;
        }

        // "Let nodes to remove be a list of all the Nodes that are contained in
        // the context object, in tree order, omitting any Node whose parent is
        // also contained in the context object."
        //
        // We rely on the fact that the contained nodes must lie in tree order
        // between the start node, and the end node's last descendant (inclusive).
        $nodesToRemove = [];
        $stop = Window::nextNodeDescendants($range->endContainer);

        for ($node = $range->startContainer; $node !== $stop; $node = Window::nextNode($node)) {
            if (
                Window::isContained($node, $range)
                && !($node->parentNode && Window::isContained($node->parentNode, $range))
            ) {
                $nodesToRemove[] = $node;
            }
        }

        // "If original start node is an ancestor container of original end node,
        // set new node to original start node and new offset to original start
        // offset."
        $newNode;
        $newOffset;

        if (
            $originalStartNode === $originalEndNode
            || $originalEndNode->compareDocumentPosition($originalStartNode) & Node::DOCUMENT_POSITION_CONTAINS
        ) {
            $newNode = $originalStartNode;
            $newOffset = $originalStartOffset;
            // "Otherwise:"
        } else {
            // "Let reference node equal original start node."
            $referenceNode = $originalStartNode;

            // "While reference node's parent is not null and is not an ancestor
            // container of original end node, set reference node to its parent."
            while (
                $referenceNode->parentNode
                && $referenceNode->parentNode !== $originalEndNode
                && !($originalEndNode->compareDocumentPosition($referenceNode->parentNode) & Node::DOCUMENT_POSITION_CONTAINS)
            ) {
                $referenceNode = $referenceNode->parentNode;
            }

            // "Set new node to the parent of reference node, and new offset to one
            // plus the index of reference node."
            $newNode = $referenceNode->parentNode;
            $newOffset = 1 + Window::indexOf($referenceNode);
        }

        // "If original start node is a Text, ProcessingInstruction, or Comment node,
        // replace data with node original start node, offset original start offset,
        // count original start node's length minus original start offset, data the
        // empty start"
        if (
            $originalStartNode->nodeType === Node::TEXT_NODE
            || $originalStartNode->nodeType === Node::PROCESSING_INSTRUCTION_NODE
            || $originalStartNode->nodeType === Node::COMMENT_NODE
        ) {
            $originalStartNode->deleteData($originalStartOffset, $originalStartNode->getLength() - $originalStartOffset);
        }

        // "For each node in nodes to remove, in order, remove node from its
        // parent."
        for ($i = 0, $length = count($nodesToRemove); $i < $length; ++$i) {
            $nodesToRemove[$i]->parentNode->removeChild($nodesToRemove[$i]);
        }

        // "If original end node is a Text, ProcessingInstruction, or Comment node,
        // replace data with node original end node, offset 0, count original end
        // offset, and data the empty string."
        if (
            $originalEndNode->nodeType === Node::TEXT_NODE
            || $originalEndNode->nodeType === Node::PROCESSING_INSTRUCTION_NODE
            || $originalEndNode->nodeType === Node::COMMENT_NODE
        ) {
            $originalEndNode->deleteData(0, $originalEndOffset);
        }

        // "Set the context object's start and end to (new node, new offset)."
        $range->setStart($newNode, $newOffset);
        $range->setEnd($newNode, $newOffset);
    }

    public static function restoreIframe(FakeIframe $iframe, int $i): void
    {
        // Most of this function is designed to work around the fact that Opera
        // doesn't let you add a doctype to a document that no longer has one, in
        // any way I can figure out.  I eventually compromised on something that
        // will still let Opera pass most tests that don't actually involve
        // doctypes.
        while (
            $iframe->contentDocument->firstChild
            && $iframe->contentDocument->firstChild->nodeType !== Node::DOCUMENT_TYPE_NODE
        ) {
            $iframe->contentDocument->removeChild($iframe->contentDocument->firstChild);
        }

        while (
            $iframe->contentDocument->lastChild
            && $iframe->contentDocument->lastChild->nodeType !== Node::DOCUMENT_TYPE_NODE
        ) {
            $iframe->contentDocument->removeChild($iframe->contentDocument->lastChild);
        }

        if (!$iframe->contentDocument->firstChild) {
            // This will throw an exception in Opera if we reach here, which is why
            // I try to avoid it.  It will never happen in a browser that obeys the
            // spec, so it's really just insurance.  I don't think it actually gets
            // hit by anything.
            $iframe->contentDocument->appendChild($iframe->contentDocument->implementation->createDocumentType("html", "", ""));
        }
        $iframe->contentDocument->appendChild(self::$referenceDoc->documentElement->cloneNode(true));
        $iframe->contentWindow->setupRangeTests();
        $iframe->contentWindow->testRangeInput = self::getWindow()->testRanges[$i];
        $iframe->contentWindow->run();
    }

    public static function getDocumentName(): string
    {
        return 'Range-deleteContents.html';
    }
}
