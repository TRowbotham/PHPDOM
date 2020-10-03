<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\DOMParser;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker-walking-outside-a-tree.html
 */
class TreeWalkerWalkingOutsideATreeTest extends TestCase
{
    private static $document;

    public function testWalkingOutsideATree(): void
    {
        $doc = self::$document->createElement('div');
        $head = self::$document->createElement('head');
        $title = self::$document->createElement('title');
        $body = self::$document->createElement('body');
        $p = self::$document->createElement('p');
        $doc->appendChild($head);
        $head->appendChild($title);
        $doc->appendChild($body);
        $body->appendChild($p);

        $w = self::$document->createTreeWalker($body, 0xFFFFFFFF, null);
        $doc->removeChild($body);
        $this->assertSame($p, $w->lastChild());
        $doc->appendChild($p);
        $this->assertSame($title, $w->previousNode());
        $p->appendChild($body);
        $this->assertSame($p, $w->nextNode());
        $this->assertSame($body, $w->nextNode());
        $this->assertNull($w->previousNode());
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<html>
<!--
Test adapted from https://github.com/operasoftware/presto-testo/blob/master/core/standards/acid3/individual/006a.html
-->
<head>
<title>TreeWalker: walking-outside-a-tree</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<script src="support/assert-node.js"></script>
<div id=log></div>
</head>
<body>
<p>[Acid3 - Test 006a] walking outside a tree</p>
<script>
test(function () {
    // test 6: walking outside a tree
    var doc = document.createElement("div");
    var head = document.createElement('head');
    var title = document.createElement('title');
    var body = document.createElement('body');
    var p = document.createElement('p');
    doc.appendChild(head);
    head.appendChild(title);
    doc.appendChild(body);
    body.appendChild(p);

    var w = document.createTreeWalker(body, 0xFFFFFFFF, null);
    doc.removeChild(body);
    assert_equals(w.lastChild(), p, "TreeWalker failed after removing the current node from the tree");
    doc.appendChild(p);
    assert_equals(w.previousNode(), title, "failed to handle regrafting correctly");
    p.appendChild(body);
    assert_equals(w.nextNode(), p, "couldn't retrace steps");
    assert_equals(w.nextNode(), body, "couldn't step back into root");
    assert_equals(w.previousNode(), null, "root didn't retake its rootish position");
}, "walking outside a tree");
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
