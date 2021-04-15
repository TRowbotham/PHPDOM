<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker-traversal-skip.html
 */
class TreeWalkerTraversalSkipTest extends TestCase
{
    private static $document;
    private static $testElement;

    public function testNextNode(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::rejectB1Filter()
        );

        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'C1'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B3'], $walker->nextNode());
    }

    public function testFirstChild(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::rejectB1Filter()
        );

        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'C1'], $walker->firstChild());
    }

    public function testNextSibling(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::rejectB2Filter()
        );

        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'B3'], $walker->nextSibling());
    }

    public function testParentNode(): void
    {
        $this->markTestIncomplete('ParentNode::querySelectorAll() is not yet implemented.');
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::rejectB1Filter()
        );
        $walker->currentNode = self::$testElement->querySelectorAll('#C1')[0];

        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->parentNode());
    }

    public function testPreviousSibling(): void
    {
        $this->markTestIncomplete('ParentNode::querySelectorAll() is not yet implemented.');
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::rejectB2Filter()
        );
        $walker->currentNode = self::$testElement->querySelectorAll('#B3')[0];

        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->previousSibling());
    }

    public function testPreviousNode(): void
    {
        $this->markTestIncomplete('ParentNode::querySelectorAll() is not yet implemented.');
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            self::rejectB1Filter()
        );
        $walker->currentNode = self::$testElement->querySelectorAll('#B3')[0];

        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->previousNode());
        $this->assertNode(['type' => Element::class, 'id' => 'C1'], $walker->previousNode());
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->previousNode());
    }

    public static function rejectB1Filter(): NodeFilter
    {
        return new class implements NodeFilter {
            public function acceptNode(Node $node): int
            {
                if ($node->id === 'B1') {
                    return NodeFilter::FILTER_SKIP;
                }

                return NodeFilter::FILTER_ACCEPT;
            }
        };
    }

    public static function rejectB2Filter(): NodeFilter
    {
        return new class implements NodeFilter {
            public function acceptNode(Node $node): int
            {
                if ($node->id === 'B2') {
                    return NodeFilter::FILTER_SKIP;
                }

                return NodeFilter::FILTER_ACCEPT;
            }
        };
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<html>
<!--
Test adapted from https://dxr.mozilla.org/chromium/source/src/third_party/WebKit/LayoutTests/fast/dom/TreeWalker/script-tests/traversal-skip.js
-->
<head>
<title>TreeWalker: traversal-skip</title>
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
    // testElement.innerHTML='<div id="A1">  <div id="B1">  <div id="C1"></div>  </div>  <div id="B2"></div><div id="B3"></div>  </div>';
    // <div id="A1">
    //   <div id="B1">
    //     <div id="C1"></div>
    //   </div>
    //   <div id="B2"></div>
    //   <div id="B3"></div>
    // </div>


    // XXX for Servo, build the tree without using innerHTML
    var a1 = document.createElement("div"); a1.id = "A1";
    var b1 = document.createElement("div"); b1.id = "B1";
    var b2 = document.createElement("div"); b2.id = "B2";
    var b3 = document.createElement("div"); b3.id = "B3";
    var c1 = document.createElement("div"); c1.id = "C1";

    testElement.appendChild(a1);
    a1.appendChild(b1);
    a1.appendChild(b2);
    a1.appendChild(b3);
    b1.appendChild(c1);
});

var skipB1Filter = {
    acceptNode: function(node) {
    if (node.id == 'B1')
        return NodeFilter.FILTER_SKIP;

    return NodeFilter.FILTER_ACCEPT;
    }
}

var skipB2Filter = {
    acceptNode: function(node) {
    if (node.id == 'B2')
        return NodeFilter.FILTER_SKIP;

    return NodeFilter.FILTER_ACCEPT;
    }
}

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, skipB1Filter);
    assert_node(walker.nextNode(), { type: Element, id: 'A1' });
    assert_node(walker.nextNode(), { type: Element, id: 'C1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B2' });
    assert_node(walker.nextNode(), { type: Element, id: 'B3' });
}, 'Testing nextNode');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, skipB1Filter);
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.firstChild(), { type: Element, id: 'C1' });
}, 'Testing firstChild');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, skipB2Filter);
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.firstChild(), { type: Element, id: 'B1' });
    assert_node(walker.nextSibling(), { type: Element, id: 'B3' });
}, 'Testing nextSibling');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, skipB1Filter);
    walker.currentNode = testElement.querySelectorAll('#C1')[0];
    assert_node(walker.parentNode(), { type: Element, id: 'A1' });
}, 'Testing parentNode');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, skipB2Filter);
    walker.currentNode = testElement.querySelectorAll('#B3')[0];
    assert_node(walker.previousSibling(), { type: Element, id: 'B1' });
}, 'Testing previousSibling');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, skipB1Filter);
    walker.currentNode = testElement.querySelectorAll('#B3')[0];
    assert_node(walker.previousNode(), { type: Element, id: 'B2' });
    assert_node(walker.previousNode(), { type: Element, id: 'C1' });
    assert_node(walker.previousNode(), { type: Element, id: 'A1' });
}, 'Testing previousNode');

</script>
</body>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
        self::$testElement = self::$document->createElement('div');
        self::$testElement->id = 'root';
        self::$testElement->innerHTML = '<div id="A1">  <div id="B1">  <div id="C1"></div>  </div>  <div id="B2"></div><div id="B3"></div>  </div>';
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$testElement = null;
    }
}
