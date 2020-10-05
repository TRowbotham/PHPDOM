<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\TestCase;

use function count;
use function file_get_contents;
use function get_class;
use function is_string;
use function substr;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-surroundContents.html
 */
class RangeSurroundContentsTest extends TestCase
{
    private static $actualIframe;
    private static $expectedIframe;
    private static $referenceDoc;
    private static $window;

    /**
     * @dataProvider rangesProvider
     */
    public function testSurroundContents(int $i, int $j): void
    {
        self::restoreIframe(self::$actualIframe, $i, $j);
        self::restoreIframe(self::$expectedIframe, $i, $j);

        $actualRange = self::$actualIframe->contentWindow->testRange;
        $expectedRange = self::$expectedIframe->contentWindow->testRange;
        $actualNode = self::$actualIframe->contentWindow->testNode;
        $expectedNode = self::$expectedIframe->contentWindow->testNode;

        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);
        $this->assertInstanceOf(Node::class, $actualNode);
        $this->assertInstanceOf(Node::class, $expectedNode);

        // We want to test that the trees containing the ranges are equal, and
        // also the trees containing the moved nodes.  These might not be the
        // same, if we're inserting a node from a detached tree or a different
        // document.
        $actualRoots[] = Window::furthestAncestor($actualRange->startContainer);
        $expectedRoots[] = Window::furthestAncestor($expectedRange->startContainer);

        $this->assertSame(count($expectedRoots), count($actualRoots));

        // This doctype stuff is to work around the fact that Opera 11.00 will
        // move around doctypes within a document, even to totally invalid
        // positions, but it won't allow a new doctype to be added to a
        // document in any way I can figure out.  So if we try moving a doctype
        // to some invalid place, in Opera it will actually succeed, and then
        // restoreIframe() will remove the doctype along with the root element,
        // and then nothing can re-add the doctype.  So instead, we catch it
        // during the test itself and move it back to the right place while we
        // still can.
        //
        // I spent *way* too much time debugging and working around this bug.
        $actualDoctype = self::$actualIframe->contentDocument->doctype;
        $expectedDoctype = self::$expectedIframe->contentDocument->doctype;

        $result;

        try {
            $result = self::mySurroundContents($expectedRange, $expectedNode);
        } catch (DOMException $e) {
            if ($expectedDoctype !== self::$expectedIframe->contentDocument->firstChild) {
                self::$expectedIframe->contentDocument->insertBefore($expectedDoctype, self::$expectedIframe->contentDocument->firstChild);
            }

            throw $e;
        }
        if (is_string($result)) {
            $this->assertThrows(static function () use ($actualNode, $actualRange, $expectedDoctype, $actualDoctype): void {
                try {
                    $actualRange->surroundContents($actualNode);
                } catch (DOMException $e) {
                    if ($expectedDoctype !== self::$expectedIframe->contentDocument->firstChild) {
                        self::$expectedIframe->contentDocument->insertBefore($expectedDoctype, self::$expectedIframe->contentDocument->firstChild);
                    }

                    if ($actualDoctype !== self::$actualIframe->contentDocument->firstChild) {
                        self::$actualIframe->contentDocument->insertBefore($actualDoctype, self::$actualIframe->contentDocument->firstChild);
                    }

                    throw $e;
                }
            }, $result);
            // Don't return, we still need to test DOM equality
        } else {
            try {
                $actualRange->surroundContents($actualNode);
            } catch (DOMException $e) {
                if ($expectedDoctype !== self::$expectedIframe->contentDocument->firstChild) {
                    self::$expectedIframe->contentDocument->insertBefore($expectedDoctype, self::$expectedIframe->contentDocument->firstChild);
                }

                if ($actualDoctype !== self::$actualIframe->contentDocument->firstChild) {
                    self::$actualIframe->contentDocument->insertBefore($actualDoctype, self::$actualIframe->contentDocument->firstChild);
                }

                throw $e;
            }
        }

        for ($k = 0, $length = count($actualRoots); $k < $length; $k++) {
            $this->assertTrue($actualRoots[$k]->isEqualNode($expectedRoots[$k]));
        }

        // Position tests
        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);
        $this->assertInstanceOf(Node::class, $actualNode);
        $this->assertInstanceOf(Node::class, $expectedNode);

        for ($k = 0, $length = count($actualRoots); $k < $length; $k++) {
            $this->assertTrue($actualRoots[$k]->isEqualNode($expectedRoots[$k]));
        }

        $this->assertSame($expectedRange->startOffset, $actualRange->startOffset);
        $this->assertSame($expectedRange->endOffset, $actualRange->endOffset);
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

    public static function mySurroundContents(Range $range, Node $newParent)
    {
        try {
            // "If a non-Text node is partially contained in the context object,
            // throw a "InvalidStateError" exception and terminate these steps."
            $node = $range->commonAncestorContainer;
            $stop = Window::nextNodeDescendants($node);

            for (; $node !== $stop; $node = Window::nextNode($node)) {
                if (
                    $node->nodeType !== Node::TEXT_NODE
                    && Window::isPartiallyContained($node, $range)
                ) {
                    return InvalidStateError::class;
                }
            }

            // "If newParent is a Document, DocumentType, or DocumentFragment node,
            // throw an "InvalidNodeTypeError" exception and terminate these
            // steps."
            if (
                $newParent->nodeType === Node::DOCUMENT_NODE
                || $newParent->nodeType === Node::DOCUMENT_TYPE_NODE
                || $newParent->nodeType === Node::DOCUMENT_FRAGMENT_NODE
            ) {
                return InvalidNodeTypeError::class;
            }

            // "Call extractContents() on the context object, and let fragment be
            // the result."
            $fragment = Window::myExtractContents($range);

            if (is_string($fragment)) {
                return $fragment;
            }

            // "While newParent has children, remove its first child."
            while (count($newParent->childNodes)) {
                $newParent->removeChild($newParent->firstChild);
            }

            // "Call insertNode(newParent) on the context object."
            $ret = Window::myInsertNode($range, $newParent);

            if (is_string($ret)) {
                return $ret;
            }

            // "Call appendChild(fragment) on newParent."
            $newParent->appendChild($fragment);

            // "Call selectNode(newParent) on the context object."
            //
            // We just reimplement this in-place.
            if (!$newParent->parentNode) {
                return InvalidNodeTypeError::class;
            }

            $index = Window::indexOf($newParent);
            $range->setStart($newParent->parentNode, $index);
            $range->setEnd($newParent->parentNode, $index + 1);
        } catch (DOMException $e) {
            return get_class($e);
        }
    }

    public static function restoreIframe(FakeIframe $iframe, int $i, int $j): void
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
        $window = self::getWindow();
        $iframe->contentWindow->testRangeInput = $window->testRangesShort[$i];
        $iframe->contentWindow->testNodeInput = $window->testNodesShort[$j];
        $iframe->contentWindow->run();
    }

    public function rangesProvider(): Generator
    {
        $window = self::getWindow();
        $document = $window->document;
        $window->setupRangeTests();
        $window->testDiv->parentNode->removeChild($window->testDiv);

        $file = __DIR__ . DS . 'resources' . DS . 'Range-test-iframe.html';
        self::$actualIframe = FakeIframe::load($file);
        self::$expectedIframe = FakeIframe::load($file);

        self::$referenceDoc = $document->implementation->createHTMLDocument('');
        self::$referenceDoc->removeChild(self::$referenceDoc->documentElement);
        self::$referenceDoc->appendChild(self::$actualIframe->contentDocument->documentElement->cloneNode(true));

        foreach ($window->testRangesShort as $i => $range) {
            foreach ($window->testNodesShort as $j => $node) {
                yield [$i, $j];
            }
        }
    }

    public static function getWindow(): Window
    {
        if (self::$window) {
            return self::$window;
        }

        $parser = new DOMParser();
        $document = $parser->parseFromString(
            file_get_contents(__DIR__ . DS . 'resources' . DS . 'Range-surroundContents.html'),
            'text/html'
        );
        self::$window = new Window($document);

        return self::$window;
    }

    public static function tearDownAfterClass(): void
    {
        self::$window = null;
    }
}
