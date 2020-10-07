<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function count;
use function is_string;
use function substr;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-extractContents.html
 */
class RangeExtractContentsTest extends RangeTestCase
{
    use WindowTrait;

    private static $actualIframe;
    private static $expectedIframe;
    private static $referenceDoc;

    /**
     * @dataProvider rangesProvider
     */
    public function testExtractContents(int $i): void
    {
        self::restoreIframe(self::$actualIframe, $i);
        self::restoreIframe(self::$expectedIframe, $i);

        $actualRange = self::$actualIframe->contentWindow->testRange;
        $expectedRange = self::$expectedIframe->contentWindow->testRange;

        $this->assertNull(self::$actualIframe->contentWindow->unexpectedException);
        $this->assertNull(self::$expectedIframe->contentWindow->unexpectedException);
        $this->assertInstanceOf(Range::class, $actualRange);
        $this->assertInstanceOf(Range::class, $expectedRange);

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

        $expectedFrag = Window::myExtractContents($expectedRange);

        if (is_string($expectedFrag)) {
            $this->assertThrows(static function () use ($actualRange): void {
                $actualRange->extractContents();
            }, $expectedFrag);
        } else {
            $actualFrag = $actualRange->extractContents();
        }

        $actualRoots = [];

        for ($j = 0, $length = count($actualAllNodes); $j < $length; $j++) {
            if (!$actualAllNodes[$j]->parentNode) {
                $actualRoots[] = $actualAllNodes[$j];
            }
        }

        $expectedRoots = [];

        for ($j = 0, $length = count($expectedAllNodes); $j < $length; $j++) {
            if (!$expectedAllNodes[$j]->parentNode) {
                $expectedRoots[] = $expectedAllNodes[$j];
            }
        }

        for ($j = 0, $length = count($actualRoots); $j < $length; $j++) {
            $this->assertTrue($actualRoots[$j]->isEqualNode($expectedRoots[$j]));

            if ($j === 0) {
                // Clearly something is wrong if the node lists are different
                // lengths.  We want to report this only after we've already
                // checked the main tree for equality, though, so it doesn't
                // mask more interesting errors.
                $this->assertSame(count($actualRoots), count($expectedRoots));
            }
        }

        // Position tests
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

        $this->assertSame($actualRange->startContainer, $actualRange->endContainer);
        $this->assertSame($actualRange->startOffset, $actualRange->endOffset);
        $this->assertSame($expectedRange->startContainer, $expectedRange->endContainer);
        $this->assertSame($expectedRange->startOffset, $expectedRange->endOffset);

        $this->assertSame($expectedRange->startOffset, $actualRange->startOffset);
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

        // Frag tests
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
        return 'Range-extractContents.html';
    }
}
