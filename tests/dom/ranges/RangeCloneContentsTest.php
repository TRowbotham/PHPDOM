<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function count;
use function is_string;
use function iterator_to_array;
use function substr;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-cloneContents.html
 */
class RangeCloneContentsTest extends RangeTestCase
{
    use WindowTrait;

    private static $referenceDoc;
    private static $actualIframe;
    private static $expectedIframe;

    /**
     * @dataProvider rangesProvider
     */
    public function testCloneContents(int $i): void
    {
        self::restoreIframe(self::$actualIframe, $i);
        self::restoreIframe(self::$expectedIframe, $i);

        $actualRange = self::$actualIframe->contentWindow->testRange;
        $expectedRange = self::$expectedIframe->contentWindow->testRange;

        // domTests
        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);

        // NOTE: We could just assume that cloneContents() doesn't change
        // anything.  That would simplify these tests, taken in isolation.  But
        // once we've already set up the whole apparatus for extractContents()
        // and deleteContents(), we just reuse it here, on the theory of "why
        // not test some more stuff if it's easy to do".
        //
        // Just to be pedantic, we'll test not only that the tree we're
        // modifying is the same in expected vs. actual, but also that all the
        // nodes originally in it were the same.  Typically some nodes will
        // become detached when the algorithm is run, but they still exist and
        // references can still be kept to them, so they should also remain the
        // same.
        //
        // We initialize the list to all nodes, and later on remove all the
        // ones which still have parents, since the parents will presumably be
        // tested for isEqualNode() and checking the children would be
        // redundant.
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

        $expectedFrag = $this->myCloneContents($expectedRange);

        if (is_string($expectedFrag)) {
            $this->assertThrows(static function () use ($actualRange): void {
                $actualRange->cloneContents();
            }, $expectedFrag);
        } else {
            $actualFrag = $actualRange->cloneContents();
        }

        $actualRoots = [];

        for ($j = 0; $j < count($actualAllNodes); $j++) {
            if (!$actualAllNodes[$j]->parentNode) {
                $actualRoots[] = $actualAllNodes[$j];
            }
        }

        $expectedRoots = [];

        for ($j = 0; $j < count($expectedAllNodes); $j++) {
            if (!$expectedAllNodes[$j]->parentNode) {
                $expectedRoots[] = $expectedAllNodes[$j];
            }
        }

        for ($j = 0; $j < count($actualRoots); $j++) {
            $this->assertTrue($actualRoots[$j]->isEqualNode($expectedRoots[$j]));

            if ($j === 0) {
                // Clearly something is wrong if the node lists are different
                // lengths.  We want to report this only after we've already
                // checked the main tree for equality, though, so it doesn't
                // mask more interesting errors.
                $this->assertSame(count($actualRoots), count($expectedRoots));
            }
        }

        // positionTests
        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);

        $this->assertTrue($actualRoots[0]->isEqualNode($expectedRoots[0]));

        if (is_string($expectedFrag)) {
            // It's no longer true that, e.g., startContainer and endContainer
            // must always be the same
            return;
        }

        $this->assertSame($expectedRange->startOffset, $actualRange->startOffset);
        // How do we decide that the two nodes are equal, since they're in
        // different trees?  Since the DOMs are the same, it's enough to check
        // that the index in the parent is the same all the way up the tree.
        // But we can first cheat by just checking they're actually equal.
        $this->assertTrue($actualRange->startContainer->isEqualNode($expectedRange->startContainer));

        $currentActual = $actualRange->startContainer;
        $currentExpected = $expectedRange->startContainer;
        $actual = "";
        $expected = "";

        while ($currentActual && $currentExpected) {
            $actual = Window::indexOf($currentActual) . "-" . $actual;
            $expected = Window::indexOf($currentExpected) . "-" . $expected;

            $currentActual = $currentActual->parentNode;
            $currentExpected = $currentExpected->parentNode;
        }

        $actual = substr($actual, 0, -1);
        $expected = substr($expected, 0, -1);
        $this->assertSame($expected, $actual);

        // fragTests
        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);

        if (is_string($expectedFrag)) {
            // Comparing makes no sense
            return;
        }

        $this->assertTrue($actualFrag->isEqualNode($expectedFrag));
    }

    public function testDetachedRange(): void
    {
        $range = self::getWindow()->document->createRange();
        $range->detach();

        $this->assertSame([], iterator_to_array($range->cloneContents()->childNodes));
    }

    public function rangesProvider(): Generator
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

        foreach ($window->testRanges as $i => $range) {
            yield [$i];
        }
    }

    /**
     * @return \Rowbot\DOM\DocumentFragment|class-string<\Rowbot\DOM\Exception\HierarchyRequestError>
     */
    public function myCloneContents(Range $range)
    {
        // "Let frag be a new DocumentFragment whose ownerDocument is the same as
        // the ownerDocument of the context object's start node."
        $ownerDoc = $range->startContainer->nodeType === Node::DOCUMENT_NODE
            ? $range->startContainer
            : $range->startContainer->ownerDocument;
        $frag = $ownerDoc->createDocumentFragment();

        // "If the context object's start and end are the same, abort this method,
        // returning frag."
        if (
            $range->startContainer === $range->endContainer
            && $range->startOffset === $range->endOffset
        ) {
            return $frag;
        }

        // "Let original start node, original start offset, original end node, and
        // original end offset be the context object's start and end nodes and
        // offsets, respectively."
        $originalStartNode = $range->startContainer;
        $originalStartOffset = $range->startOffset;
        $originalEndNode = $range->endContainer;
        $originalEndOffset = $range->endOffset;

        // "If original start node and original end node are the same, and they are
        // a Text, ProcessingInstruction, or Comment node:"
        if (
            $range->startContainer === $range->endContainer
            && (
                $range->startContainer->nodeType === Node::TEXT_NODE
                || $range->startContainer->nodeType === Node::COMMENT_NODE
                || $range->startContainer->nodeType === Node::PROCESSING_INSTRUCTION_NODE
            )
        ) {
            // "Let clone be the result of calling cloneNode(false) on original
            // start node."
            $clone = $originalStartNode->cloneNode(false);

            // "Set the data of clone to the result of calling
            // substringData(original start offset, original end offset − original
            // start offset) on original start node."
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                $originalEndOffset - $originalStartOffset
            );

            // "Append clone as the last child of frag."
            $frag->appendChild($clone);

            // "Abort this method, returning frag."
            return $frag;
        }

        // "Let common ancestor equal original start node."
        $commonAncestor = $originalStartNode;

        // "While common ancestor is not an ancestor container of original end
        // node, set common ancestor to its own parent."
        while (!Window::isAncestorContainer($commonAncestor, $originalEndNode)) {
            $commonAncestor = $commonAncestor->parentNode;
        }

        // "If original start node is an ancestor container of original end node,
        // let first partially contained child be null."
        $firstPartiallyContainedChild;

        if (Window::isAncestorContainer($originalStartNode, $originalEndNode)) {
            $firstPartiallyContainedChild = null;
        // "Otherwise, let first partially contained child be the first child of
        // common ancestor that is partially contained in the context object."
        } else {
            for ($i = 0; $i < $commonAncestor->childNodes->count(); $i++) {
                if (Window::isPartiallyContained($commonAncestor->childNodes[$i], $range)) {
                    $firstPartiallyContainedChild = $commonAncestor->childNodes[$i];

                    break;
                }
            }

            if (!$firstPartiallyContainedChild) {
                throw "Spec bug: no first partially contained child!";
            }
        }

        // "If original end node is an ancestor container of original start node,
        // let last partially contained child be null."
        $lastPartiallyContainedChild;

        if (Window::isAncestorContainer($originalEndNode, $originalStartNode)) {
            $lastPartiallyContainedChild = null;
        // "Otherwise, let last partially contained child be the last child of
        // common ancestor that is partially contained in the context object."
        } else {
            for ($i = $commonAncestor->childNodes->count() - 1; $i >= 0; $i--) {
                if (Window::isPartiallyContained($commonAncestor->childNodes[$i], $range)) {
                    $lastPartiallyContainedChild = $commonAncestor->childNodes[$i];

                    break;
                }
            }

            if (!$lastPartiallyContainedChild) {
                throw "Spec bug: no last partially contained child!";
            }
        }

        // "Let contained children be a list of all children of common ancestor
        // that are contained in the context object, in tree order."
        //
        // "If any member of contained children is a DocumentType, raise a
        // HIERARCHY_REQUEST_ERR exception and abort these steps."
        $containedChildren = [];

        for ($i = 0; $i < $commonAncestor->childNodes->count(); $i++) {
            if (Window::isContained($commonAncestor->childNodes[$i], $range)) {
                if ($commonAncestor->childNodes[$i]->nodeType === Node::DOCUMENT_TYPE_NODE) {
                    return HierarchyRequestError::class;
                }

                $containedChildren[] = $commonAncestor->childNodes[$i];
            }
        }

        // "If first partially contained child is a Text, ProcessingInstruction, or Comment node:"
        if (
            $firstPartiallyContainedChild
            && (
                $firstPartiallyContainedChild->nodeType === Node::TEXT_NODE
                || $firstPartiallyContainedChild->nodeType === Node::COMMENT_NODE
                || $firstPartiallyContainedChild->nodeType === Node::PROCESSING_INSTRUCTION_NODE
            )
        ) {
            // "Let clone be the result of calling cloneNode(false) on original
            // start node."
            $clone = $originalStartNode->cloneNode(false);

            // "Set the data of clone to the result of calling substringData() on
            // original start node, with original start offset as the first
            // argument and (length of original start node − original start offset)
            // as the second."
            $clone->data = $originalStartNode->substringData(
                $originalStartOffset,
                Window::nodeLength($originalStartNode) - $originalStartOffset
            );

            // "Append clone as the last child of frag."
            $frag->appendChild($clone);

        // "Otherwise, if first partially contained child is not null:"
        } elseif ($firstPartiallyContainedChild) {
            // "Let clone be the result of calling cloneNode(false) on first
            // partially contained child."
            $clone = $firstPartiallyContainedChild->cloneNode(false);

            // "Append clone as the last child of frag."
            $frag->appendChild($clone);

            // "Let subrange be a new Range whose start is (original start node,
            // original start offset) and whose end is (first partially contained
            // child, length of first partially contained child)."
            $subrange = $ownerDoc->createRange();
            $subrange->setStart($originalStartNode, $originalStartOffset);
            $subrange->setEnd(
                $firstPartiallyContainedChild,
                Window::nodeLength($firstPartiallyContainedChild)
            );

            // "Let subfrag be the result of calling cloneContents() on
            // subrange."
            $subfrag = $this->myCloneContents($subrange);

            // "For each child of subfrag, in order, append that child to clone as
            // its last child."
            for ($i = 0; $i < $subfrag->childNodes->count(); $i++) {
                $clone->appendChild($subfrag->childNodes[$i]);
            }
        }

        // "For each contained child in contained children:"
        for ($i = 0; $i < count($containedChildren); $i++) {
            // "Let clone be the result of calling cloneNode(true) of contained
            // child."
            $clone = $containedChildren[$i]->cloneNode(true);

            // "Append clone as the last child of frag."
            $frag->appendChild($clone);
        }

        // "If last partially contained child is a Text, ProcessingInstruction, or Comment node:"
        if (
            $lastPartiallyContainedChild
            && (
                $lastPartiallyContainedChild->nodeType == Node::TEXT_NODE
                || $lastPartiallyContainedChild->nodeType == Node::COMMENT_NODE
                || $lastPartiallyContainedChild->nodeType == Node::PROCESSING_INSTRUCTION_NODE
            )
        ) {
            // "Let clone be the result of calling cloneNode(false) on original
            // end node."
            $clone = $originalEndNode->cloneNode(false);

            // "Set the data of clone to the result of calling substringData(0,
            // original end offset) on original end node."
            $clone->data = $originalEndNode->substringData(0, $originalEndOffset);

            // "Append clone as the last child of frag."
            $frag->appendChild($clone);

        // "Otherwise, if last partially contained child is not null:"
        } elseif ($lastPartiallyContainedChild) {
            // "Let clone be the result of calling cloneNode(false) on last
            // partially contained child."
            $clone = $lastPartiallyContainedChild->cloneNode(false);

            // "Append clone as the last child of frag."
            $frag->appendChild($clone);

            // "Let subrange be a new Range whose start is (last partially
            // contained child, 0) and whose end is (original end node, original
            // end offset)."
            $subrange = $ownerDoc->createRange();
            $subrange->setStart($lastPartiallyContainedChild, 0);
            $subrange->setEnd($originalEndNode, $originalEndOffset);

            // "Let subfrag be the result of calling cloneContents() on
            // subrange."
            $subfrag = $this->myCloneContents($subrange);

            // "For each child of subfrag, in order, append that child to clone as
            // its last child."
            for ($i = 0; $i < $subfrag->childNodes->count(); $i++) {
                $clone->appendChild($subfrag->childNodes[$i]);
            }
        }

        // "Return frag."
        return $frag;
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
        return 'Range-cloneContents.html';
    }
}
