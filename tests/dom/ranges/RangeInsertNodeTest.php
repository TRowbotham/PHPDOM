<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Rowbot\DOM\Exception\DOMException;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function array_unshift;
use function is_string;

use const DIRECTORY_SEPARATOR as DS;

class RangeInsertNodeTest extends RangeTestCase
{
    use WindowTrait;

    private static $referenceDoc;
    private static $actualIframe;
    private static $expectedIframe;

    /**
     * @dataProvider rangesProvider
     */
    public function testInsertNode(int $i, int $j): void
    {
        $actualRoots = [];
        $expectedRoots = [];
        $detached = false;

        self::restoreIframe(self::$actualIframe, $i, $j);
        self::restoreIframe(self::$expectedIframe, $i, $j);

        $actualRange = self::$actualIframe->contentWindow->testRange;
        $expectedRange = self::$expectedIframe->contentWindow->testRange;
        $actualNode = self::$actualIframe->contentWindow->testNode;
        $expectedNode = self::$expectedIframe->contentWindow->testNode;

        try {
            $actualRange->collapsed;
        } catch (Exception $e) {
            $detached = true;
        }

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
        //
        // Detached ranges are always in the contentDocument.
        if ($detached) {
            $actualRoots[] = self::$actualIframe->contentDocument;
            $expectedRoots[] = self::$expectedIframe->contentDocument;
        } else {
            $actualRoots[] = Window::furthestAncestor($actualRange->startContainer);
            $expectedRoots[] = Window::furthestAncestor($expectedRange->startContainer);
        }

        if (Window::furthestAncestor($actualNode) !== $actualRoots[0]) {
            $actualRoots[] = Window::furthestAncestor($actualNode);
        }

        if (Window::furthestAncestor($expectedNode) !== $expectedRoots[0]) {
            $expectedRoots[] = Window::furthestAncestor($expectedNode);
        }

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
            $result = Window::myInsertNode($expectedRange, $expectedNode);
        } catch (DOMException $e) {
            if ($expectedDoctype !== self::$expectedIframe->contentDocument->firstChild) {
                self::$expectedIframe->contentDocument->insertBefore($expectedDoctype, self::$expectedIframe->contentDocument->firstChild);
            }

            throw $e;
        }

        if (is_string($result)) {
            $this->assertThrows(static function () use ($actualRange, $actualNode, $expectedDoctype, $actualDoctype) {
                try {
                    $actualRange->insertNode($actualNode);
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
                $actualRange->insertNode($actualNode);
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

        foreach ($actualRoots as $k => $actualRoot) {
            $this->assertTrue($actualRoot->isEqualNode($expectedRoots[$k]));
        }
    }

    public function rangesProvider()
    {
        $window = self::getWindow();
        $window->initStrings();

        array_unshift($window->testRanges, '"detached"');

        foreach ($window->testRangesShort as $i => $range) {
            foreach ($window->testNodesShort as $j => $node) {
                yield [$i, $j];
            }
        }
    }

    public static function setUpBeforeClass(): void
    {
        $window = self::getWindow();
        $document = $window->document;
        $window->setupRangeTests();
        $window->testDiv->parentNode->removeChild($window->testDiv);

        array_unshift($window->testRanges, '"detached"');

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

    public static function getDocumentName(): string
    {
        return 'Range-insertNode.html';
    }
}
