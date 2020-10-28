<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\NodeFilter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker-currentNode.html
 */
class TreeWalkerCurrentNodeTest extends TestCase
{
    private static $document;
    private static $subTree;

    public function testTreeWalkerParentDoesntSetTheCurrentNodeToANodeNotUnderTheRoot(): void
    {
        $w = self::$document->createTreeWalker(
            self::$subTree,
            NodeFilter::SHOW_ELEMENT,
            self::class . '::all'
        );

        $this->assertNode(['type' => Element::class, 'id' => 'subTree'], $w->currentNode);
        $this->assertNull($w->parentNode());
        $this->assertNode(['type' => Element::class, 'id' => 'subTree'], $w->currentNode);
    }

    public function testHandleSettingTheCurrentNodeToArbitraryNodesNotUnderTheRootElement(): void
    {
        $w = self::$document->createTreeWalker(
            self::$subTree,
            NodeFilter::SHOW_ELEMENT | NodeFilter::SHOW_COMMENT,
            self::class . '::all'
        );
        $w->currentNode = self::$document->documentElement;

        $this->assertNull($w->parentNode());
        $this->assertSame(self::$document->documentElement, $w->currentNode);

        $w->currentNode = self::$document->documentElement;

        $this->assertSame(self::$document->documentElement->firstChild, $w->nextNode());
        $this->assertSame(self::$document->documentElement->firstChild, $w->currentNode);

        $w->currentNode = self::$document->documentElement;

        $this->assertNull($w->parentNode());
        $this->assertSame(self::$document->documentElement, $w->currentNode);

        $w->currentNode = self::$document->documentElement;

        $this->assertSame(self::$document->documentElement->firstChild, $w->firstChild());
        $this->assertSame(self::$document->documentElement->firstChild, $w->currentNode);

        $w->currentNode = self::$document->documentElement;

        $this->assertSame(self::$document->documentElement->lastChild, $w->lastChild());
        $this->assertSame(self::$document->documentElement->lastChild, $w->currentNode);

        $w->currentNode = self::$document->documentElement;

        $this->assertNull($w->nextSibling());
        $this->assertSame(self::$document->documentElement, $w->currentNode);

        $w->currentNode = self::$document->documentElement;

        $this->assertNull($w->previousSibling());
        $this->assertSame(self::$document->documentElement, $w->currentNode);
    }

    public function testHandleTheCaseWhenTheTraversedToNodeIsWithinTheRootButTheCurrentElementIsNot(): void
    {
        $w = self::$document->createTreeWalker(
            self::$subTree,
            NodeFilter::SHOW_ELEMENT,
            self::class . '::all'
        );
        $w->currentNode = self::$subTree->previousSibling;
        $this->assertSame(self::$subTree, $w->nextNode());
        $w->currentNode = self::$document->getElementById('parent');
        $this->assertSame(self::$subTree, $w->firstChild());
    }

    public static function all(): int
    {
        // return true;
        return NodeFilter::FILTER_ACCEPT;
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<html>
<!--
Test adapted from https://dxr.mozilla.org/chromium/source/src/third_party/WebKit/LayoutTests/fast/dom/TreeWalker/resources/TreeWalker-currentNode.js
-->
<head>
<title>TreeWalker: currentNode</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<script src="support/assert-node.js"></script>
<div id=log></div>
</head>
<body>
<div id='parent'>
<div id='subTree'><p>Lorem ipsum <span>dolor <b>sit</b> amet</span>, consectetur <i>adipisicing</i> elit, sed do eiusmod <tt>tempor <b><i>incididunt ut</i> labore</b> et dolore magna</tt> aliqua.</p></div>
</div>
<p>Test TreeWalker currentNode functionality</p>
<script>
// var subTree = document.createElement('div');
// subTree.innerHTML = "<p>Lorem ipsum <span>dolor <b>sit</b> amet</span>, consectetur <i>adipisicing</i> elit, sed do eiusmod <tt>tempor <b><i>incididunt ut</i> labore</b> et dolore magna</tt> aliqua.</p>"
// document.body.appendChild(subTree);
var subTree = document.getElementById("subTree");

var all = function(node) { return true; }

test(function()
{
    var w = document.createTreeWalker(subTree, NodeFilter.SHOW_ELEMENT, all);
    assert_node(w.currentNode, { type: Element, id: 'subTree' });
    assert_equals(w.parentNode(), null);
    assert_node(w.currentNode, { type: Element, id: 'subTree' });
}, "Test that TreeWalker.parent() doesn't set the currentNode to a node not under the root.");

test(function()
{
    var w = document.createTreeWalker(subTree,
                                        NodeFilter.SHOW_ELEMENT
                                        | NodeFilter.SHOW_COMMENT,
                                        all);
    w.currentNode = document.documentElement;
    assert_equals(w.parentNode(), null);
    assert_equals(w.currentNode, document.documentElement);
    w.currentNode = document.documentElement;
    assert_equals(w.nextNode(), document.documentElement.firstChild);
    assert_equals(w.currentNode, document.documentElement.firstChild);
    w.currentNode = document.documentElement;
    assert_equals(w.previousNode(), null);
    assert_equals(w.currentNode, document.documentElement);
    w.currentNode = document.documentElement;
    assert_equals(w.firstChild(), document.documentElement.firstChild);
    assert_equals(w.currentNode, document.documentElement.firstChild);
    w.currentNode = document.documentElement;
    assert_equals(w.lastChild(), document.documentElement.lastChild);
    assert_equals(w.currentNode, document.documentElement.lastChild);
    w.currentNode = document.documentElement;
    assert_equals(w.nextSibling(), null);
    assert_equals(w.currentNode, document.documentElement);
    w.currentNode = document.documentElement;
    assert_equals(w.previousSibling(), null);
    assert_equals(w.currentNode, document.documentElement);
}, "Test that we handle setting the currentNode to arbitrary nodes not under the root element.");

test(function()
{
    var w = document.createTreeWalker(subTree, NodeFilter.SHOW_ELEMENT, all);
    w.currentNode = subTree.previousSibling;
    assert_equals(w.nextNode(), subTree);
    w.currentNode = document.getElementById("parent");
    assert_equals(w.firstChild(), subTree);
}, "Test how we handle the case when the traversed to node is within the root, but the currentElement is not.");
</script>
</body>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
        self::$subTree = self::$document->getElementById('subTree');
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$subTree = null;
    }
}
