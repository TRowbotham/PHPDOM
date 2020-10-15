<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-constructor.html
 */
class RangeConstructorTest extends TestCase
{
    public function testConstructorFoo(): void
    {
        $document = $this->getDocument();
        $range = new Range($document);

        $this->assertSame($document, $range->startContainer);
        $this->assertSame($document, $range->endContainer);
        $this->assertSame(0, $range->startOffset);
        $this->assertSame(0, $range->endOffset);
        $this->assertTrue($range->collapsed);
        $this->assertSame($document, $range->commonAncestorContainer);
    }

    public function getDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range constructor test</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script>
"use strict";

test(function() {
    var range = new Range();
    assert_equals(range.startContainer, document, "startContainer");
    assert_equals(range.endContainer, document, "endContainer");
    assert_equals(range.startOffset, 0, "startOffset");
    assert_equals(range.endOffset, 0, "endOffset");
    assert_true(range.collapsed, "collapsed");
    assert_equals(range.commonAncestorContainer, document,
                    "commonAncestorContainer");
});
</script>
TEST_HTML;

        $p = new DOMParser();

        return $p->parseFromString($html, 'text/html');
    }
}
