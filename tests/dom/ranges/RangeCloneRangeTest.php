<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-cloneRange.html
 */
class RangeCloneRangeTest extends TestCase
{
    use Common;

    /**
     * @dataProvider rangeProvider
     */
    public function testCloneRange(array $rangeEndpoints): void
    {
        global $document;

        if ($rangeEndpoints === 'detached') {
            $range = $document->createRange();
            $range->detach();
            $clonedRange = $range->cloneRange();

            $this->assertSame($range->startContainer, $clonedRange->startContainer);
            $this->assertSame($range->startOffset, $clonedRange->startOffset);
            $this->assertSame($range->endContainer, $clonedRange->endContainer);
            $this->assertSame($range->endOffset, $clonedRange->endOffset);

            return;
        }

        $ownerDoc = $rangeEndpoints[0]->nodeType === Node::DOCUMENT_NODE
            ? $rangeEndpoints[0]
            : $rangeEndpoints[0]->ownerDocument;
        $range = $ownerDoc->createRange();

        // Here we throw in some createRange() tests, because why not.  Have to
        // test it someplace.
        $this->assertSame($ownerDoc, $range->startContainer);
        $this->assertSame($ownerDoc, $range->endContainer);
        $this->assertSame(0, $range->startOffset);
        $this->assertSame(0, $range->endOffset);

        $range->setStart($rangeEndpoints[0], $rangeEndpoints[1]);
        $range->setEnd($rangeEndpoints[2], $rangeEndpoints[3]);

        // Make sure we bail out now if setStart or setEnd are buggy, so it doesn't
        // create misleading failures later.
        $this->assertSame($rangeEndpoints[0], $range->startContainer);
        $this->assertSame($rangeEndpoints[1], $range->startOffset);
        $this->assertSame($rangeEndpoints[2], $range->endContainer);
        $this->assertSame($rangeEndpoints[3], $range->endOffset);

        $clonedRange = $range->cloneRange();

        $this->assertSame($range->startContainer, $clonedRange->startContainer);
        $this->assertSame($range->startOffset, $clonedRange->startOffset);
        $this->assertSame($range->endContainer, $clonedRange->endContainer);
        $this->assertSame($range->endOffset, $clonedRange->endOffset);

        // Make sure that modifying one doesn't affect the other.
        $testNode1 = $ownerDoc->createTextNode('testing');
        $testNode2 = $ownerDoc->createTextNode('testing with different length');

        $range->setStart($testNode1, 1);
        $range->setEnd($testNode1, 2);

        $this->assertSame($rangeEndpoints[0], $clonedRange->startContainer);
        $this->assertSame($rangeEndpoints[1], $clonedRange->startOffset);
        $this->assertSame($rangeEndpoints[2], $clonedRange->endContainer);
        $this->assertSame($rangeEndpoints[3], $clonedRange->endOffset);

        $clonedRange->setStart($testNode2, 3);
        $clonedRange->setEnd($testNode2, 4);

        $this->assertSame($testNode1, $range->startContainer);
        $this->assertSame(1, $range->startOffset);
        $this->assertSame($testNode1, $range->endContainer);
        $this->assertSame(2, $range->endOffset);
    }

    public function rangeProvider(): array
    {
        global $document, $testRanges;

        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.cloneRange() and document.createRange() tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

function testCloneRange(rangeEndpoints) {
    var range;
    if (rangeEndpoints == "detached") {
    range = document.createRange();
    range.detach();
    var clonedRange = range.cloneRange();
    assert_equals(clonedRange.startContainer, range.startContainer,
        "startContainers must be equal after cloneRange()");
    assert_equals(clonedRange.startOffset, range.startOffset,
        "startOffsets must be equal after cloneRange()");
    assert_equals(clonedRange.endContainer, range.endContainer,
        "endContainers must be equal after cloneRange()");
    assert_equals(clonedRange.endOffset, range.endOffset,
        "endOffsets must be equal after cloneRange()");
    return;
    }

    // Have to account for Ranges involving Documents!  We could just create
    // the Range from the current document unconditionally, but some browsers
    // (WebKit) don't implement setStart() and setEnd() per spec and will throw
    // spurious exceptions at the time of this writing.  No need to mask other
    // bugs.
    var ownerDoc = rangeEndpoints[0].nodeType == Node.DOCUMENT_NODE
    ? rangeEndpoints[0]
    : rangeEndpoints[0].ownerDocument;
    range = ownerDoc.createRange();
    // Here we throw in some createRange() tests, because why not.  Have to
    // test it someplace.
    assert_equals(range.startContainer, ownerDoc,
    "doc.createRange() must create Range whose startContainer is doc");
    assert_equals(range.endContainer, ownerDoc,
    "doc.createRange() must create Range whose endContainer is doc");
    assert_equals(range.startOffset, 0,
    "doc.createRange() must create Range whose startOffset is 0");
    assert_equals(range.endOffset, 0,
    "doc.createRange() must create Range whose endOffset is 0");

    range.setStart(rangeEndpoints[0], rangeEndpoints[1]);
    range.setEnd(rangeEndpoints[2], rangeEndpoints[3]);

    // Make sure we bail out now if setStart or setEnd are buggy, so it doesn't
    // create misleading failures later.
    assert_equals(range.startContainer, rangeEndpoints[0],
    "Sanity check on setStart()");
    assert_equals(range.startOffset, rangeEndpoints[1],
    "Sanity check on setStart()");
    assert_equals(range.endContainer, rangeEndpoints[2],
    "Sanity check on setEnd()");
    assert_equals(range.endOffset, rangeEndpoints[3],
    "Sanity check on setEnd()");

    var clonedRange = range.cloneRange();

    assert_equals(clonedRange.startContainer, range.startContainer,
    "startContainers must be equal after cloneRange()");
    assert_equals(clonedRange.startOffset, range.startOffset,
    "startOffsets must be equal after cloneRange()");
    assert_equals(clonedRange.endContainer, range.endContainer,
    "endContainers must be equal after cloneRange()");
    assert_equals(clonedRange.endOffset, range.endOffset,
    "endOffsets must be equal after cloneRange()");

    // Make sure that modifying one doesn't affect the other.
    var testNode1 = ownerDoc.createTextNode("testing");
    var testNode2 = ownerDoc.createTextNode("testing with different length");

    range.setStart(testNode1, 1);
    range.setEnd(testNode1, 2);
    assert_equals(clonedRange.startContainer, rangeEndpoints[0],
    "Modifying a Range must not modify its clone's startContainer");
    assert_equals(clonedRange.startOffset, rangeEndpoints[1],
    "Modifying a Range must not modify its clone's startOffset");
    assert_equals(clonedRange.endContainer, rangeEndpoints[2],
    "Modifying a Range must not modify its clone's endContainer");
    assert_equals(clonedRange.endOffset, rangeEndpoints[3],
    "Modifying a Range must not modify its clone's endOffset");

    clonedRange.setStart(testNode2, 3);
    clonedRange.setStart(testNode2, 4);

    assert_equals(range.startContainer, testNode1,
    "Modifying a clone must not modify the original Range's startContainer");
    assert_equals(range.startOffset, 1,
    "Modifying a clone must not modify the original Range's startOffset");
    assert_equals(range.endContainer, testNode1,
    "Modifying a clone must not modify the original Range's endContainer");
    assert_equals(range.endOffset, 2,
    "Modifying a clone must not modify the original Range's endOffset");
}

var tests = [];
for (var i = 0; i < testRanges.length; i++) {
    tests.push([
    "Range " + i + " " + testRanges[i],
    eval(testRanges[i])
    ]);
}
generate_tests(testCloneRange, tests);

testDiv.style.display = "none";
</script>
TEST_HTML;
        $p = new DOMParser();
        $document = $p->parseFromString($html, 'text/html');
        self::setupRangeTests($document);
        $tests = [];

        foreach ($testRanges as $i => $range) {
            $tests["Range {$i} {$range}"] = [$this->eval($range, $document)];
        }

        return $tests;
    }
}
