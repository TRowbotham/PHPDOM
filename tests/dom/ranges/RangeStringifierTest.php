<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-stringifier.html
 */
class RangeStringifierTest extends TestCase
{
    protected static $data = [];

    public function testNodeContentsOfSingleDiv(): void
    {
        [$r, $testDiv] = $this->setupDOM();
        $r->selectNodeContents($testDiv);

        $this->assertFalse($r->collapsed);
        $this->assertSame($testDiv->textContent, $r->toString());
    }

    public function testTextNodeWithOffsets(): void
    {
        [$r, $testDiv, $textNode] = $this->setupDOM();
        $r->setStart($textNode, 5);
        $r->setEnd($textNode, 7);

        $this->assertFalse($r->collapsed);
        $this->assertSame('di', $r->toString());
    }

    public function testTwoNodesEachWithATextNode(): void
    {
        [$r, $testDiv, $textNode, $anotherDiv] = $this->setupDOM();
        $r->setStart($testDiv, 0);
        $r->setEnd($anotherDiv, 0);

        $this->assertSame("Test div\n", $r->toString());
    }

    public function testThreeNodesWithStartOffsetAndEndOffsetOnTextNodes(): void
    {
        [$r, $testDiv, $textNode, $anotherDiv, $lastDiv, $lastText] = $this->setupDOM();
        $r->setStart($textNode, 5);
        $r->setEnd($lastText, 4);

        $this->assertSame("div\nAnother div\nLast", $r->toString());
    }

    public function setupDOM(): array
    {
        if (self::$data !== []) {
            return self::$data;
        }

        $p = new DOMParser();
        $html = <<<'TEST_HTML'
<!doctype html>
<meta charset="utf-8">
<title>Range stringifier</title>
<link rel="author" title="KiChjang" href="mailto:kungfukeith11@gmail.com">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<div id=test>Test div</div>
<div id=another>Another div</div>
<div id=last>Last div</div>
<div id=log></div>
<script>
test(function() {
    var r = new Range();
    var testDiv = document.getElementById("test");
    test(function() {
    r.selectNodeContents(testDiv);
    assert_equals(r.collapsed, false);
    assert_equals(r.toString(), testDiv.textContent);
    }, "Node contents of a single div");

    var textNode = testDiv.childNodes[0];
    test(function() {
    r.setStart(textNode, 5);
    r.setEnd(textNode, 7);
    assert_equals(r.collapsed, false);
    assert_equals(r.toString(), "di");
    }, "Text node with offsets");

    var anotherDiv = document.getElementById("another");
    test(function() {
    r.setStart(testDiv, 0);
    r.setEnd(anotherDiv, 0);
    assert_equals(r.toString(), "Test div\n");
    }, "Two nodes, each with a text node");

    var lastDiv = document.getElementById("last");
    var lastText = lastDiv.childNodes[0];
    test(function() {
    r.setStart(textNode, 5);
    r.setEnd(lastText, 4);
    assert_equals(r.toString(), "div\nAnother div\nLast");
    }, "Three nodes with start offset and end offset on text nodes");
});
</script>
TEST_HTML;

        $doc = $p->parseFromString($html, 'text/html');
        $testDiv = $doc->getElementById('test');
        $textNode = $testDiv->childNodes[0];
        $anotherDiv = $doc->getElementById('another');
        $lastDiv = $doc->getElementById('last');
        $lastText = $lastDiv->childNodes[0];

        self::$data = [
            $doc->createRange(),
            $testDiv,
            $textNode,
            $anotherDiv,
            $lastDiv,
            $lastText,
        ];

        return self::$data;
    }
}
