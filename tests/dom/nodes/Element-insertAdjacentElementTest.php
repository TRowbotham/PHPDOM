<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-insertAdjacentElement.html
 */
class ElementInsertAdjacentElementTest extends TestCase
{
    private static $document;
    private static $target;
    private static $target2;

    public function testInsertingToInvalidLocationThrowsSyntaxError(): void
    {
        $this->assertThrows(static function (): void {
            self::$target->insertAdjacentElement('test', self::$document->getElementById('test1'));
        }, SyntaxError::class);

        $this->assertThrows(static function (): void {
            self::$target2->insertAdjacentElement('test', self::$document->getElementById('test1'));
        }, SyntaxError::class);
    }

    public function testInsertedElementShouldBeTargetElementsPreviousSiblingForBeforebeginCase(): void
    {
        $el = self::$target->insertAdjacentElement('beforebegin', self::$document->getElementById('test1'));
        $this->assertSame('test1', self::$target->previousSibling->id);
        $this->assertSame('test1', $el->id);

        $el = self::$target2->insertAdjacentElement('beforebegin', self::$document->getElementById('test1'));
        $this->assertSame('test1', self::$target2->previousSibling->id);
        $this->assertSame('test1', $el->id);
    }

    public function testInsertedElementShouldBeTargetElementsFirstChildForAfterbeginCase(): void
    {
        $el = self::$target->insertAdjacentElement('afterbegin', self::$document->getElementById('test2'));
        $this->assertSame('test2', self::$target->firstChild->id);
        $this->assertSame('test2', $el->id);

        $el = self::$target2->insertAdjacentElement('afterbegin', self::$document->getElementById('test2'));
        $this->assertSame('test2', self::$target2->firstChild->id);
        $this->assertSame('test2', $el->id);
    }

    public function testInsertedElementShouldBeTargetElementsLastChildForBeforeendCase(): void
    {
        $el = self::$target->insertAdjacentElement('beforeend', self::$document->getElementById('test3'));
        $this->assertSame('test3', self::$target->lastChild->id);
        $this->assertSame('test3', $el->id);

        $el = self::$target2->insertAdjacentElement('beforeend', self::$document->getElementById('test3'));
        $this->assertSame('test3', self::$target2->lastChild->id);
        $this->assertSame('test3', $el->id);
    }

    public function testInsertedElementShouldBeTargetElementsNextSiblingForAfterendCase(): void
    {
        $el = self::$target->insertAdjacentElement('afterend', self::$document->getElementById('test4'));
        $this->assertSame('test4', self::$target->nextSibling->id);
        $this->assertSame('test4', $el->id);

        $el = self::$target2->insertAdjacentElement('afterend', self::$document->getElementById('test4'));
        $this->assertSame('test4', self::$target2->nextSibling->id);
        $this->assertSame('test4', $el->id);
    }

    public function testAddingMoreThanOneChildToDocumentShouldCauseHierarchyRequestError(): void
    {
        $docElement = self::$document->documentElement;
        // $docElement->style->visibility = 'hidden';
        $el = null;

        $this->assertThrows(static function () use (&$el, $docElement): void {
            $el = $docElement->insertAdjacentElement('beforebegin', self::$document->getElementById('test1'));
        }, HierarchyRequestError::class);
        $this->assertNull($el);

        $el = $docElement->insertAdjacentElement('afterbegin', self::$document->getElementById('test2'));
        $this->assertSame('test2', $docElement->firstChild->id);
        $this->assertSame('test2', $el->id);

        $el = $docElement->insertAdjacentElement('beforeend', self::$document->getElementById('test3'));
        $this->assertSame('test3', $docElement->lastChild->id);
        $this->assertSame('test3', $el->id);

        $el = null;
        $this->assertThrows(static function () use (&$el, $docElement): void {
            $el = $docElement->insertAdjacentElement('afterend', self::$document->getElementById('test4'));
        }, HierarchyRequestError::class);
        $this->assertNull($el);
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<meta charset=utf-8>
<title></title>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>

<div id="target"></div>
<div id="parent"><span id=target2></span></div>
<div id="log" style="visibility:visible"></div>
<span id="test1"></span>
<span id="test2"></span>
<span id="test3"></span>
<span id="test4"></span>
<script>
var target = document.getElementById("target");
var target2 = document.getElementById("target2");

test(function() {
    assert_throws_dom("SyntaxError", function() {
    target.insertAdjacentElement("test", document.getElementById("test1"))
    });

    assert_throws_dom("SyntaxError", function() {
    target2.insertAdjacentElement("test", document.getElementById("test1"))
    });
}, "Inserting to an invalid location should cause a Syntax Error exception")

test(function() {
    var el = target.insertAdjacentElement("beforebegin", document.getElementById("test1"));
    assert_equals(target.previousSibling.id, "test1");
    assert_equals(el.id, "test1");

    el = target2.insertAdjacentElement("beforebegin", document.getElementById("test1"));
    assert_equals(target2.previousSibling.id, "test1");
    assert_equals(el.id, "test1");
}, "Inserted element should be target element's previous sibling for 'beforebegin' case")

test(function() {
    var el = target.insertAdjacentElement("afterbegin", document.getElementById("test2"));
    assert_equals(target.firstChild.id, "test2");
    assert_equals(el.id, "test2");

    el = target2.insertAdjacentElement("afterbegin", document.getElementById("test2"));
    assert_equals(target2.firstChild.id, "test2");
    assert_equals(el.id, "test2");
}, "Inserted element should be target element's first child for 'afterbegin' case")

test(function() {
    var el = target.insertAdjacentElement("beforeend", document.getElementById("test3"));
    assert_equals(target.lastChild.id, "test3");
    assert_equals(el.id, "test3");

    el = target2.insertAdjacentElement("beforeend", document.getElementById("test3"));
    assert_equals(target2.lastChild.id, "test3");
    assert_equals(el.id, "test3");
}, "Inserted element should be target element's last child for 'beforeend' case")

test(function() {
    var el = target.insertAdjacentElement("afterend", document.getElementById("test4"));
    assert_equals(target.nextSibling.id, "test4");
    assert_equals(el.id, "test4");

    el = target2.insertAdjacentElement("afterend", document.getElementById("test4"));
    assert_equals(target2.nextSibling.id, "test4");
    assert_equals(el.id, "test4");
}, "Inserted element should be target element's next sibling for 'afterend' case")

test(function() {
    var docElement = document.documentElement;
    docElement.style.visibility="hidden";

    assert_throws_dom("HierarchyRequestError", function() {
    var el = docElement.insertAdjacentElement("beforebegin", document.getElementById("test1"));
    assert_equals(el, null);
    });

    var el = docElement.insertAdjacentElement("afterbegin", document.getElementById("test2"));
    assert_equals(docElement.firstChild.id, "test2");
    assert_equals(el.id, "test2");

    el = docElement.insertAdjacentElement("beforeend", document.getElementById("test3"));
    assert_equals(docElement.lastChild.id, "test3");
    assert_equals(el.id, "test3");

    assert_throws_dom("HierarchyRequestError", function() {
    var el = docElement.insertAdjacentElement("afterend", document.getElementById("test4"));
    assert_equals(el, null);
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
