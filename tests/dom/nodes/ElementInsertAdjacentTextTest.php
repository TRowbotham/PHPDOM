<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-insertAdjacentText.html
 */
class ElementInsertAdjacentTextTest extends TestCase
{
    private static $document;
    private static $target;
    private static $target2;

    public function testInsertingToAnInvalidLocationShouldCauseSyntaxError(): void
    {
        $this->assertThrows(static function (): void {
            self::$target->insertAdjacentText('test', 'text');
        }, SyntaxError::class);
        $this->assertThrows(static function (): void {
            self::$target2->insertAdjacentText('test', 'test');
        }, SyntaxError::class);
    }

    public function testInsertedTextNodeShouldBeTargetElementsPreviousSiblingForBeforebeginCase(): void
    {
        self::$target->insertAdjacentText('beforebegin', 'test1');
        $this->assertSame('test1', self::$target->previousSibling->nodeValue);

        self::$target2->insertAdjacentText('beforebegin', 'test1');
        $this->assertSame('test1', self::$target2->previousSibling->nodeValue);
    }

    public function testInsertedTextNodeShouldBeTargetElementsFirstChildForAfterbeginCase(): void
    {
        self::$target->insertAdjacentText('afterbegin', 'test2');
        $this->assertSame('test2', self::$target->firstChild->nodeValue);

        self::$target2->insertAdjacentText('afterbegin', 'test2');
        $this->assertSame('test2', self::$target2->firstChild->nodeValue);
    }

    public function testInsertedTextNodeShouldBeTargetElementsLastChildForBeforeendCase(): void
    {
        self::$target->insertAdjacentText('beforeend', 'test3');
        $this->assertSame('test3', self::$target->lastChild->nodeValue);

        self::$target2->insertAdjacentText('beforeend', 'test3');
        $this->assertSame('test3', self::$target2->lastChild->nodeValue);
    }

    public function testInsertedTextNodeShouldBeTargetElementsNextSiblingForAfterendCase(): void
    {
        self::$target->insertAdjacentText('afterend', 'test4');
        $this->assertSame('test4', self::$target->nextSibling->nodeValue);

        self::$target2->insertAdjacentText('afterend', 'test4');
        $this->assertSame('test4', self::$target2->nextSibling->nodeValue);
    }

    public function testAddingMoreThanOneChildToDocumentShouldCauseHierarchyRequestError(): void
    {
        $docElement = self::$document->documentElement;
        // $docElement->style->visibility = 'hidden';

        $this->assertThrows(static function () use ($docElement): void {
            $docElement->insertAdjacentText('beforebegin', 'text1');
        }, HierarchyRequestError::class);

        $docElement->insertAdjacentText('afterbegin', 'test2');
        $this->assertSame('test2', $docElement->firstChild->nodeValue);

        $docElement->insertAdjacentText('beforeend', 'test3');
        $this->assertSame('test3', $docElement->lastChild->nodeValue);

        $this->assertThrows(static function () use ($docElement): void {
            $docElement->insertAdjacentText('afterend', 'test4');
        }, HierarchyRequestError::class);
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<meta charset=utf-8>
<title></title>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<body style="visibility:hidden">
<div id="target"></div>
<div id="parent"><span id=target2></span></div>
<div id="log" style="visibility:visible"></div>
</body>
<script>
var target = document.getElementById("target");
var target2 = document.getElementById("target2");

test(function() {
    assert_throws_dom("SyntaxError", function() {
    target.insertAdjacentText("test", "text")
    });

    assert_throws_dom("SyntaxError", function() {
    target2.insertAdjacentText("test", "test")
    });
}, "Inserting to an invalid location should cause a Syntax Error exception")

test(function() {
    target.insertAdjacentText("beforebegin", "test1");
    assert_equals(target.previousSibling.nodeValue, "test1");

    target2.insertAdjacentText("beforebegin", "test1");
    assert_equals(target2.previousSibling.nodeValue, "test1");
}, "Inserted text node should be target element's previous sibling for 'beforebegin' case")

test(function() {
    target.insertAdjacentText("afterbegin", "test2");
    assert_equals(target.firstChild.nodeValue, "test2");

    target2.insertAdjacentText("afterbegin", "test2");
    assert_equals(target2.firstChild.nodeValue, "test2");
}, "Inserted text node should be target element's first child for 'afterbegin' case")

test(function() {
    target.insertAdjacentText("beforeend", "test3");
    assert_equals(target.lastChild.nodeValue, "test3");

    target2.insertAdjacentText("beforeend", "test3");
    assert_equals(target2.lastChild.nodeValue, "test3");
}, "Inserted text node should be target element's last child for 'beforeend' case")

test(function() {
    target.insertAdjacentText("afterend", "test4");
    assert_equals(target.nextSibling.nodeValue, "test4");

    target2.insertAdjacentText("afterend", "test4");
    assert_equals(target.nextSibling.nodeValue, "test4");
}, "Inserted text node should be target element's next sibling for 'afterend' case")

test(function() {
    var docElement = document.documentElement;
    docElement.style.visibility="hidden";

    assert_throws_dom("HierarchyRequestError", function() {
    docElement.insertAdjacentText("beforebegin", "text1")
    });

    docElement.insertAdjacentText("afterbegin", "test2");
    assert_equals(docElement.firstChild.nodeValue, "test2");

    docElement.insertAdjacentText("beforeend", "test3");
    assert_equals(docElement.lastChild.nodeValue, "test3");

    assert_throws_dom("HierarchyRequestError", function() {
    docElement.insertAdjacentText("afterend", "test4")
    });
}, "Adding more than one child to document should cause a HierarchyRequestError exception")

</script>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
        self::$target = self::$document->getElementById('target');
        self::$target2 = self::$document->getElementById('target2');
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$target = null;
        self::$target2 = null;
    }
}
