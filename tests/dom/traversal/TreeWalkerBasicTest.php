<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\traversal;

use ArgumentCountError;
use Rowbot\DOM\Comment;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Text;
use Rowbot\DOM\TreeWalker;
use TypeError;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker-basic.html
 */
class TreeWalkerBasicTest extends TestCase
{
    use DocumentGetter;

    private static $document;

    public function testConstructTreeWalkerSingleArgument(): void
    {
        $root = $this->createSampleDOM();
        $walker = self::$document->createTreeWalker($root);
        $this->checkWalker($walker, $root);
    }

    public function testContructTreeWalkerNullArguments(): void
    {
        $this->expectException(TypeError::class);
        $root = $this->createSampleDOM();
        $walker = self::$document->createTreeWalker($root, null, null);
        $this->checkWalker($walker, $root, 0);
    }

    public function testConstructNoArguments(): void
    {
        $this->expectException(ArgumentCountError::class);
        $root = $this->createSampleDOM();
        $walker = self::$document->createTreeWalker();
        $this->checkWalker($walker, $root);
    }

    public function testWalkOverNodes(): void
    {
        $root = $this->createSampleDOM();
        $walker = self::$document->createTreeWalker($root);
        $f = $root->lastChild->firstChild->childNodes[1]; // An element node div#f

        $this->assertNode(['type' => Element::class, 'id' => 'a'], $walker->currentNode);
        $this->assertNull($walker->parentNode());
        $this->assertNode(['type' => Element::class, 'id' => 'a'], $walker->currentNode);
        $this->assertNode(['type' => Text::class, 'nodeValue' => 'b'], $walker->firstChild());
        $this->assertNode(['type' => Text::class, 'nodeValue' => 'b'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'c'], $walker->nextSibling());
        $this->assertNode(['type' => Element::class, 'id' => 'c'], $walker->currentNode);
        $this->assertNode(['type' => Comment::class, 'nodeValue' => 'j'], $walker->lastChild());
        $this->assertNode(['type' => Comment::class, 'nodeValue' => 'j'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'd'], $walker->previousSibling());
        $this->assertNode(['type' => Element::class, 'id' => 'd'], $walker->currentNode);
        $this->assertNode(['type' => Text::class, 'nodeValue' => 'e'], $walker->nextNode());
        $this->assertNode(['type' => Text::class, 'nodeValue' => 'e'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'd'], $walker->parentNode());
        $this->assertNode(['type' => Element::class, 'id' => 'd'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'c'], $walker->previousNode());
        $this->assertNode(['type' => Element::class, 'id' => 'c'], $walker->currentNode);
        $this->assertNull($walker->nextSibling());
        $this->assertNode(['type' => Element::class, 'id' => 'c'], $walker->currentNode);
        $walker->currentNode = $f;
        $this->assertSame($f, $walker->currentNode);
    }

    public function testOptionalArgumentsShouldBeOptional(): void
    {
        $treeWalker = self::$document->createTreeWalker(self::$document->body, 42, null);
        $this->assertSame(self::$document->body, $treeWalker->root);
        $this->assertSame(self::$document->body, $treeWalker->currentNode);
        $this->assertSame(42, $treeWalker->whatToShow);
        $this->assertNull($treeWalker->filter);
    }

    public function createSampleDOM(): Element
    {
        // Tree structure:
        //             #a
        //             |
        //        +----+----+
        //        |         |
        //       "b"        #c
        //                  |
        //             +----+----+
        //             |         |
        //            #d      <!--j-->
        //             |
        //        +----+----+
        //        |    |    |
        //       "e"  #f   "i"
        //             |
        //          +--+--+
        //          |     |
        //         "g" <!--h-->
        $div = self::$document->createElement('div');
        $div->id = 'a';
        $div->innerHTML = 'b<div id="c"><div id="d">e<span id="f">g<!--h--></span>i</div><!--j--></div>';

        return $div;
    }

    public function checkWalker(TreeWalker $walker, Node $root, int $whatToShow = 0xFFFFFFFF): void
    {
        // $this->assertSame('[object TreeWalker]', $walker->toString());
        $this->assertSame($root, $walker->root, 'root');
        $this->assertSame($whatToShow, $walker->whatToShow, 'whatToShow');
        $this->assertNull($walker->filter);
        $this->assertSame($root, $walker->currentNode);
        // assert_readonly(walker, 'root');
        // assert_readonly(walker, 'whatToShow');
        // assert_readonly(walker, 'filter');
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<html>
<!--
Test adapted from https://dxr.mozilla.org/chromium/source/src/third_party/WebKit/LayoutTests/fast/dom/TreeWalker/TreeWalker-basic.html
-->
<head>
<title>TreeWalker: Basic test</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<script src="support/assert-node.js"></script>
<div id=log></div>
</head>
<body>
<p>This test checks the basic functionality of TreeWalker.</p>
<script>
function createSampleDOM()
{
    // Tree structure:
    //             #a
    //             |
    //        +----+----+
    //        |         |
    //       "b"        #c
    //                  |
    //             +----+----+
    //             |         |
    //            #d      <!--j-->
    //             |
    //        +----+----+
    //        |    |    |
    //       "e"  #f   "i"
    //             |
    //          +--+--+
    //          |     |
    //         "g" <!--h-->
    var div = document.createElement('div');
    div.id = 'a';
    // div.innerHTML = 'b<div id="c"><div id="d">e<span id="f">g<!--h--></span>i</div><!--j--></div>';

    div.appendChild(document.createTextNode("b"));

    var c = document.createElement("div");
    c.id = 'c';
    div.appendChild(c);

    var d = document.createElement("div");
    d.id = 'd';
    c.appendChild(d);

    var e = document.createTextNode("e");
    d.appendChild(e);

    var f = document.createElement("span");
    f.id = 'f';
    d.appendChild(f);

    var g = document.createTextNode("g");
    f.appendChild(g);

    var h = document.createComment("h");
    f.appendChild(h);

    var i = document.createTextNode("i");
    d.appendChild(i);

    var j = document.createComment("j");
    c.appendChild(j);

    return div;
}

function check_walker(walker, root, whatToShowValue)
{
    whatToShowValue = whatToShowValue === undefined ? 0xFFFFFFFF : whatToShowValue;

    assert_equals(walker.toString(), '[object TreeWalker]', 'toString');
    assert_equals(walker.root, root, 'root');
    assert_equals(walker.whatToShow, whatToShowValue, 'whatToShow');
    assert_equals(walker.filter, null, 'filter');
    assert_equals(walker.currentNode, root, 'currentNode');
    assert_readonly(walker, 'root');
    assert_readonly(walker, 'whatToShow');
    assert_readonly(walker, 'filter');
}

test(function ()
{
    var root = createSampleDOM();
    var walker = document.createTreeWalker(root);
    check_walker(walker, root);
}, 'Construct a TreeWalker by document.createTreeWalker(root).');

test(function ()
{
    var root = createSampleDOM();
    var walker = document.createTreeWalker(root, null, null);
    check_walker(walker, root, 0);
}, 'Construct a TreeWalker by document.createTreeWalker(root, null, null).');

test(function ()
{
    var root = createSampleDOM();
    var walker = document.createTreeWalker(root, undefined, undefined);
    check_walker(walker, root);
}, 'Construct a TreeWalker by document.createTreeWalker(root, undefined, undefined).');

test(function ()
{
    assert_throws_js(TypeError, function () { document.createTreeWalker(); });
    assert_throws_js(TypeError, function () { document.createTreeWalker(null); });
    assert_throws_js(TypeError, function () { document.createTreeWalker(undefined); });
    assert_throws_js(TypeError, function () { document.createTreeWalker(new Object()); });
    assert_throws_js(TypeError, function () { document.createTreeWalker(1); });
}, 'Give an invalid root node to document.createTreeWalker().');

test(function ()
{
    var root = createSampleDOM();
    var walker = document.createTreeWalker(root);
    var f = root.lastChild.firstChild.childNodes[1];  // An element node: div#f.

    assert_node(walker.currentNode, { type: Element, id: 'a' });
    assert_equals(walker.parentNode(), null);
    assert_node(walker.currentNode, { type: Element, id: 'a' });
    assert_node(walker.firstChild(), { type: Text, nodeValue: 'b' });
    assert_node(walker.currentNode, { type: Text, nodeValue: 'b' });
    assert_node(walker.nextSibling(), { type: Element, id: 'c' });
    assert_node(walker.currentNode, { type: Element, id: 'c' });
    assert_node(walker.lastChild(), { type: Comment, nodeValue: 'j' });
    assert_node(walker.currentNode, { type: Comment, nodeValue: 'j' });
    assert_node(walker.previousSibling(), { type: Element, id: 'd' });
    assert_node(walker.currentNode, { type: Element, id: 'd' });
    assert_node(walker.nextNode(), { type: Text, nodeValue: 'e' });
    assert_node(walker.currentNode, { type: Text, nodeValue: 'e' });
    assert_node(walker.parentNode(), { type: Element, id: 'd' });
    assert_node(walker.currentNode, { type: Element, id: 'd' });
    assert_node(walker.previousNode(), { type: Element, id: 'c' });
    assert_node(walker.currentNode, { type: Element, id: 'c' });
    assert_equals(walker.nextSibling(), null);
    assert_node(walker.currentNode, { type: Element, id: 'c' });
    walker.currentNode = f;
    assert_equals(walker.currentNode, f);
}, 'Walk over nodes.');

test(function() {
    var treeWalker = document.createTreeWalker(document.body, 42, null);
    assert_equals(treeWalker.root, document.body);
    assert_equals(treeWalker.currentNode, document.body);
    assert_equals(treeWalker.whatToShow, 42);
    assert_equals(treeWalker.filter, null);
}, "Optional arguments to createTreeWalker should be optional (3 passed, null).");
</script>
</body>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
