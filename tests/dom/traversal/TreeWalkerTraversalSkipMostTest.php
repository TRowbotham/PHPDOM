<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker-traversal-skip-most.html
 */
class TreeWalkerTraversalSkipMostTest extends TestCase
{
    private static $document;
    private static $testElement;

    public function testNextSibling(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::filter()
        );

        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'B3'], $walker->nextSibling());
    }

    public function testPreviousSibling(): void
    {
        $this->markTestIncomplete('ParentNode::querySelectorAll() is not yet implemented.');
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::filter()
        );
        $walker->currentNode = self::$testElement->querySelectorAll('#B3')[0];

        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->previousSibling());
    }

    public static function filter(): NodeFilter
    {
        return new class implements NodeFilter {
            public function acceptNode(Node $node): int
            {
                if ($node->className === 'keep') {
                    return NodeFilter::FILTER_ACCEPT;
                }

                return NodeFilter::FILTER_SKIP;
            }
        };
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<html>
<!--
Test adapted from https://dxr.mozilla.org/chromium/source/src/third_party/WebKit/LayoutTests/fast/dom/TreeWalker/script-tests/traversal-skip-most.js
-->
<head>
<title>TreeWalker: traversal-skip-most</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<script src="support/assert-node.js"></script>
<div id=log></div>
</head>
<body>
<p>Test TreeWalker with skipping</p>
<script>
var testElement;
setup(function() {
    testElement = document.createElement("div");
    testElement.id = 'root';
    // testElement.innerHTML='<div id="A1"><div id="B1" class="keep"></div><div id="B2">this text matters</div><div id="B3" class="keep"></div></div>';
    // <div id="A1">
    //   <div id="B1" class="keep"></div>
    //   <div id="B2">this text matters</div>
    //   <div id="B3" class="keep"></div>
    // </div>


    // XXX for Servo, build the tree without using innerHTML
    var a1 = document.createElement("div"); a1.id = "A1";
    var b1 = document.createElement("div"); b1.id = "B1"; b1.className = "keep";
    var b2 = document.createElement("div"); b2.id = "B2";
    var b3 = document.createElement("div"); b3.id = "B3"; b3.className = "keep";

    testElement.appendChild(a1);
    a1.appendChild(b1);
    a1.appendChild(b2)
        .appendChild(document.createTextNode("this text matters"));
    a1.appendChild(b3);
});

var filter = {
    acceptNode: function(node) {
    if (node.className == 'keep')
        return NodeFilter.FILTER_ACCEPT;

    return NodeFilter.FILTER_SKIP;
    }
}

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, filter);
    assert_node(walker.firstChild(), { type: Element, id: 'B1' });
    assert_node(walker.nextSibling(), { type: Element, id: 'B3' });
}, 'Testing nextSibling');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, filter);
    walker.currentNode = testElement.querySelectorAll('#B3')[0];
    assert_node(walker.previousSibling(), { type: Element, id: 'B1' });
}, 'Testing previousSibling');

</script>
</body>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
        self::$testElement = self::$document->createElement('div');
        self::$testElement->id = 'root';
        self::$testElement->innerHTML = '<div id="A1"><div id="B1" class="keep"></div><div id="B2">this text matters</div><div id="B3" class="keep"></div></div>';
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$testElement = null;
    }
}
