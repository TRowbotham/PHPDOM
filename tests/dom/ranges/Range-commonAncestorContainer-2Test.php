<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-commonAncestorContainer-2.html
 */
class RangeCommonAncestorContainer2Test extends TestCase
{
    public function testDetachedRange(): void
    {
        $document = $this->getDocument();
        $range = $document->createRange();
        $range->detach();

        $this->assertSame($document, $range->commonAncestorContainer);
    }

    public function testNormalRanges(): void
    {
        $document = $this->getDocument();
        $df = $document->createDocumentFragment();
        $foo = $df->appendChild($document->createElement('foo'));
        $foo->appendChild($document->createTextNode('Foo'));
        $bar = $df->appendChild($document->createElement('bar'));
        $bar->appendChild($document->createComment("Bar"));
        $tests = [
            // start node, start offset, end node, end offset, expected cAC
            [$foo, 0, $bar, 0, $df],
            [$foo, 0, $foo->firstChild, 3, $foo],
            [$foo->firstChild, 0, $bar, 0, $df],
            [$foo->firstChild, 3, $bar->firstChild, 2, $df],
        ];

        foreach ($tests as $t) {
            $range = $document->createRange();
            $range->setStart($t[0], $t[1]);
            $range->setEnd($t[2], $t[3]);

            $this->assertSame($t[4], $range->commonAncestorContainer);
        }
    }

    public function getDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.commonAncestorContainer</title>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<div id=log></div>
<script>
test(function() {
    var range = document.createRange();
    range.detach();
    assert_equals(range.commonAncestorContainer, document);
}, "Detached Range")
test(function() {
    var df = document.createDocumentFragment();
    var foo = df.appendChild(document.createElement("foo"));
    foo.appendChild(document.createTextNode("Foo"));
    var bar = df.appendChild(document.createElement("bar"));
    bar.appendChild(document.createComment("Bar"));
    [
    // start node, start offset, end node, end offset, expected cAC
    [foo, 0, bar, 0, df],
    [foo, 0, foo.firstChild, 3, foo],
    [foo.firstChild, 0, bar, 0, df],
    [foo.firstChild, 3, bar.firstChild, 2, df]
    ].forEach(function(t) {
    test(function() {
        var range = document.createRange();
        range.setStart(t[0], t[1]);
        range.setEnd(t[2], t[3]);
        assert_equals(range.commonAncestorContainer, t[4]);
    })
    });
}, "Normal Ranges")
</script>
TEST_HTML;

        $p = new DOMParser();

        return $p->parseFromString($html, 'text/html');
    }
}
