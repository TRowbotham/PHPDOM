<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-collapse.html
 */
class RangeCollapseTest extends TestCase
{
    use Common;

    /**
     * @dataProvider rangeProvider
     */
    public function testCollapse(array $rangeEndpoints, ?bool $toStart): void
    {
        global $document;

        if ($rangeEndpoints === 'detached') {
            $range = $document->createRange();
            $range->detach(); // should be a no-op and therefore the following should not throw
            $range->collapse($toStart);

            $this->assertTrue($range->collapsed);
        }

        // Have to account for Ranges involving Documents!
        $ownerDoc = $rangeEndpoints[0]->nodeType === Node::DOCUMENT_NODE
            ? $rangeEndpoints[0]
            : $rangeEndpoints[0]->ownerDocument;
        $range = $ownerDoc->createRange();
        $range->setStart($rangeEndpoints[0], $rangeEndpoints[1]);
        $range->setEnd($rangeEndpoints[2], $rangeEndpoints[3]);

        $expectedContainer = $toStart ? $range->startContainer : $range->endContainer;
        $expectedOffset = $toStart ? $range->startOffset : $range->endOffset;

        $this->assertSame($range->startContainer === $range->endContainer && $range->startOffset === $range->endOffset, $range->collapsed);

        if ($toStart === null) {
            $range->collapse();
        } else {
            $range->collapse($toStart);
        }

        $this->assertSame($expectedContainer, $range->startContainer);
        $this->assertSame($expectedContainer, $range->endContainer);
        $this->assertSame($expectedOffset, $range->startOffset);
        $this->assertSame($expectedOffset, $range->endOffset);
        $this->assertTrue($range->collapsed);
    }

    public function rangeProvider(): array
    {
        global $document, $testRanges;

        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.collapse() and .collapsed tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

function testCollapse(rangeEndpoints, toStart) {
    var range;
    if (rangeEndpoints == "detached") {
    range = document.createRange();
    range.detach(); // should be a no-op and therefore the following should not throw
    range.collapse(toStart);
    assert_equals(true, range.collapsed);
    }

    // Have to account for Ranges involving Documents!
    var ownerDoc = rangeEndpoints[0].nodeType == Node.DOCUMENT_NODE
    ? rangeEndpoints[0]
    : rangeEndpoints[0].ownerDocument;
    range = ownerDoc.createRange();
    range.setStart(rangeEndpoints[0], rangeEndpoints[1]);
    range.setEnd(rangeEndpoints[2], rangeEndpoints[3]);

    var expectedContainer = toStart ? range.startContainer : range.endContainer;
    var expectedOffset = toStart ? range.startOffset : range.endOffset;

    assert_equals(range.collapsed, range.startContainer == range.endContainer
    && range.startOffset == range.endOffset,
    "collapsed must be true if and only if the start and end are equal");

    if (toStart === undefined) {
    range.collapse();
    } else {
    range.collapse(toStart);
    }

    assert_equals(range.startContainer, expectedContainer,
    "Wrong startContainer");
    assert_equals(range.endContainer, expectedContainer,
    "Wrong endContainer");
    assert_equals(range.startOffset, expectedOffset,
    "Wrong startOffset");
    assert_equals(range.endOffset, expectedOffset,
    "Wrong endOffset");
    assert_true(range.collapsed,
    ".collapsed must be set after .collapsed()");
}

var tests = [];
for (var i = 0; i < testRanges.length; i++) {
    tests.push([
    "Range " + i + " " + testRanges[i] + ", toStart true",
    eval(testRanges[i]),
    true
    ]);
    tests.push([
    "Range " + i + " " + testRanges[i] + ", toStart false",
    eval(testRanges[i]),
    false
    ]);
    tests.push([
    "Range " + i + " " + testRanges[i] + ", toStart omitted",
    eval(testRanges[i]),
    undefined
    ]);
}
generate_tests(testCollapse, tests);

testDiv.style.display = "none";
</script>
TEST_HTML;

        $p = new DOMParser();
        $document = $p->parseFromString($html, 'text/html');
        self::setupRangeTests($document);
        $tests = [];

        foreach ($testRanges as $i => $range) {
            $tests["Range {$i} {$range}, toStart true"] = [$this->eval($range, $document), true];
            $tests["Range {$i} {$range}, toStart false"] = [$this->eval($range, $document), false];
            $tests["Range {$i} {$range}, toStart omitted"] = [$this->eval($range, $document), null];
        }

        return $tests;
    }
}
