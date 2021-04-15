<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker-previousNodeLastChildReject.html
 */
class TreeWalkerPreviousNodeLastChildRejectTest extends TestCase
{
    private static $document;
    private static $testElement;

    public function testThatPreviousNodeProperlyRespectsTheFilter(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            static function (Node $node): int {
                if ($node->id === 'C2') {
                    return NodeFilter::FILTER_REJECT;
                }

                return NodeFilter::FILTER_ACCEPT;
            }
        );

        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'C1'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'C1'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'C1'], $walker->previousNode());
        $this->assertNode(['type' => Element::class, 'id' => 'C1'], $walker->currentNode);
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<html>
<!--
Test adapted from https://dxr.mozilla.org/chromium/source/src/third_party/WebKit/LayoutTests/fast/dom/TreeWalker/script-tests/previousNodeLastChildReject.js
-->
<head>
<title>TreeWalker: previousNodeLastChildReject</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<script src="support/assert-node.js"></script>
<div id=log></div>
</head>
<body>
<p>Test that previousNode properly respects the filter.</p>
<script>
var testElement;
setup(function() {
    testElement = document.createElement("div");
    testElement.id = 'root';
    // testElement.innerHTML='<div id="A1"><div id="B1"><div id="C1"></div><div id="C2"><div id="D1"></div><div id="D2"></div></div></div><div id="B2"><div id="C3"></div><div id="C4"></div></div></div>';
    // testElement.innerHTML='
    // <div id="A1">
    //   <div id="B1">
    //     <div id="C1">
    //     </div>
    //     <div id="C2">
    //       <div id="D1">
    //       </div>
    //       <div id="D2">
    //       </div>
    //     </div>
    //   </div>
    //   <div id="B2">
    //     <div id="C3">
    //     </div>
    //     <div id="C4">
    //     </div>
    //   </div>
    // </div>';

    // XXX for Servo, build the tree without using innerHTML
    var a1 = document.createElement("div"); a1.id = "A1";
    var b1 = document.createElement("div"); b1.id = "B1";
    var b2 = document.createElement("div"); b2.id = "B2";
    var c1 = document.createElement("div"); c1.id = "C1";
    var c2 = document.createElement("div"); c2.id = "C2";
    var c3 = document.createElement("div"); c3.id = "C3";
    var c4 = document.createElement("div"); c4.id = "C4";
    var d1 = document.createElement("div"); d1.id = "D1";
    var d2 = document.createElement("div"); d2.id = "D2";

    testElement.appendChild(a1);
    a1.appendChild(b1);
    a1.appendChild(b2);
    b1.appendChild(c1);
    b1.appendChild(c2);
    b2.appendChild(c3);
    b2.appendChild(c4);
    c2.appendChild(d1);
    c2.appendChild(d2);
});

test(function()
{
    function filter(node)
    {
        if (node.id == "C2")
            return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
    }

    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, filter);
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.currentNode, { type: Element, id: 'A1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B1' });
    assert_node(walker.currentNode, { type: Element, id: 'B1' });
    assert_node(walker.nextNode(), { type: Element, id: 'C1' });
    assert_node(walker.currentNode, { type: Element, id: 'C1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B2' });
    assert_node(walker.currentNode, { type: Element, id: 'B2' });
    assert_node(walker.previousNode(), { type: Element, id: 'C1' });
    assert_node(walker.currentNode, { type: Element, id: 'C1' });
}, 'Test that previousNode properly respects the filter.');
</script>
</body>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
        self::$testElement = self::$document->createElement('div');
        self::$testElement->id = 'root';
        self::$testElement->innerHTML = '<div id="A1"><div id="B1"><div id="C1"></div><div id="C2"><div id="D1"></div><div id="D2"></div></div></div><div id="B2"><div id="C3"></div><div id="C4"></div></div></div>';
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$testElement = null;
    }
}
