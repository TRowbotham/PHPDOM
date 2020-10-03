<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\HTML\HTMLDivElement;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-getElementById.html
 */
class DocumentGetElementByIdTest extends TestCase
{
    private static $document;
    private static $gBody;

    public function testCallingGetElementByIdWithEmptyString(): void
    {
        $this->assertNull(self::$document->getElementById(''));
    }

    public function testGetElementByIdOnStaticPage(): void
    {
        $bar = self::$document->getElementById('test1');

        $this->assertNotNull($bar);
        $this->assertSame('DIV', $bar->tagName);
        $this->assertInstanceOf(HTMLDivElement::class, $bar);
    }

    public function testGetElementByIdWithScriptedInsert(): void
    {
        $TEST_ID = 'test2';

        // test: appended element
        $test = self::$document->createElement('div');
        $test->setAttribute('id', $TEST_ID);
        self::$gBody->appendChild($test);

        // test: removed element
        $result = self::$document->getElementById($TEST_ID);
        $this->assertNotNull($result);
        $this->assertSame('DIV', $result->tagName);
        $this->assertInstanceOf(HTMLDivElement::class, $result);

        self::$gBody->removeChild($test);
        $removed = self::$document->getElementById($TEST_ID);

        $this->assertNull($removed);
    }

    public function testGetElementByIdWhenIdUpdatedBySetAttribute(): void
    {
        $TEST_ID = 'test3';
        $test = self::$document->createElement('div');
        $test->setAttribute('id', $TEST_ID);
        self::$gBody->appendChild($test);

        // update id
        $UPDATED_ID = 'test3-updated';
        $test->setAttribute('id', $UPDATED_ID);
        $e = self::$document->getElementById($UPDATED_ID);

        $this->assertSame($test, $e);

        $old = self::$document->getElementById($TEST_ID);
        $this->assertNull($old);

        // remove id
        $test->removeAttribute('id');
        $e2 = self::$document->getElementById($UPDATED_ID);

        $this->assertNull($e2);
    }

    public function testEnsureIdAttributeOnlyAffectsElementsPresentInDocument(): void
    {
        $TEST_ID = 'test4-should-not-exist';

        $e = self::$document->createElement('div');
        $e->setAttribute('id', $TEST_ID);

        $this->assertNull(self::$document->getElementById($TEST_ID));
        self::$document->body->appendChild($e);
        $this->assertSame($e, self::$document->getElementById($TEST_ID));
    }

    public function testGetElementByIdInTreeOrder(): void
    {
        // the method should return the 1st element.
        $TEST_ID = 'test5';
        $target = self::$document->getElementById($TEST_ID);

        $this->assertNotNull($target);
        $this->assertSame('1st', $target->getAttribute('data-name'));

        // even if after the new element was appended
        $element4 = self::$document->createElement('div');
        $element4->setAttribute('id', $TEST_ID);
        $element4->setAttribute('data-name', '4th');
        self::$gBody->appendChild($element4);
        $target2 = self::$document->getElementById($TEST_ID);

        $this->assertNotNull($target2);
        $this->assertSame('1st', $target2->getAttribute('data-name'));

        // should return the next element after removed the subtree including the 1st element.
        $target2->parentNode->removeChild($target2);
        $target3 = self::$document->getElementById($TEST_ID);
        $this->assertNotNull($target3);
        $this->assertSame('4th', $target3->getAttribute('data-name'));
    }

    public function test6(): void
    {
        $TEST_ID = 'test6';
        $s = self::$document->createElement('div');
        $s->setAttribute('id', $TEST_ID);
        self::$document->createElement('div')->appendChild($s);

        $this->assertNull(self::$document->getElementById($TEST_ID));
    }

    public function test7(): void
    {
        $TEST_ID = 'test7';
        $element = self::$document->createElement('div');
        $element->setAttribute('id', $TEST_ID);
        self::$gBody->appendChild($element);

        $target = self::$document->getElementById($TEST_ID);
        $this->assertSame($element, $target);

        $element->attributes[0]->value = $TEST_ID . '-updated';
        $target2 = self::$document->getElementById($TEST_ID);
        $this->assertNull($target2);
        $target3 = self::$document->getElementById($TEST_ID . '-updated');
        $this->assertSame($element, $target3);
    }

    public function testAddIdAttributeViaInnerHTML(): void
    {
        $TEST_ID = 'test8';

        $element = self::$document->createElement('div');
        $element->setAttribute('id', $TEST_ID . '-fixture');
        self::$gBody->appendChild($element);

        $element->innerHTML = "<div id='" . $TEST_ID . "'></div>";
        $test = self::$document->getElementById($TEST_ID);

        $this->assertSame($element->firstChild, $test);
        $this->assertSame('DIV', $test->tagName);
        $this->assertInstanceOf(HTMLDivElement::class, $test);
    }

    public function testRemoveIdAttributeViaInnerHTML(): void
    {
        $TEST_ID = 'test9';

        $fixture = self::$document->createElement('div');
        $fixture->setAttribute('id', $TEST_ID . '-fixture');
        self::$gBody->appendChild($fixture);

        $element = self::$document->createElement('div');
        $element->setAttribute('id', $TEST_ID);
        $fixture->appendChild($element);

        // check 'getElementById' should get the 'element'
        $this->assertSame($element, self::$document->getElementById($TEST_ID));

        // remove id-ed element with using innerHTML (clear 'element')
        $fixture->innerHTML = '';
        $test = self::$document->getElementById($TEST_ID);
        $this->assertNull($test);
    }

    public function testAddIdAttributeViaOuterHTML(): void
    {
        $TEST_ID = 'test10';

        $element = self::$document->createElement('div');
        $element->setAttribute('id', $TEST_ID . '-fixture');
        self::$gBody->appendChild($element);

        $element->outerHTML = "<div id='" . $TEST_ID . "'></div>";
        $test = self::$document->getElementById($TEST_ID);

        $this->assertNotNull($test);
        $this->assertSame('DIV', $test->tagName);
        $this->assertInstanceOf(HTMLDivElement::class, $test);
    }

    public function testRemoveIdAttributeViaOuterHTML(): void
    {
        $TEST_ID = 'test11';

        $element = self::$document->createElement('div');
        $element->setAttribute('id', $TEST_ID);
        self::$gBody->appendChild($element);

        $test = self::$document->getElementById($TEST_ID);
        $this->assertSame($element, $test);

        // remove id-ed element with using outerHTML
        $element->outerHTML = '<div></div>';
        $test = self::$document->getElementById($TEST_ID);
        $this->assertNull($test);
    }

    public function testUpdateIdViaElementId(): void
    {
        $TEST_ID = 'test12';
        $test = self::$document->createElement('div');
        $test->id = $TEST_ID;
        self::$gBody->appendChild($test);

        // update id
        $UPDATED_ID = $TEST_ID . '-updated';
        $test->id = $UPDATED_ID;
        $e = self::$document->getElementById($UPDATED_ID);
        $this->assertSame($test, $e);

        $old = self::$document->getElementById($TEST_ID);
        $this->assertNull($old);

        // remove id
        $test->id = '';
        $e2 = self::$document->getElementById($UPDATED_ID);
        $this->assertNull($e2);
    }

    public function testWhereInsertionOrderAndTreeOrderDontMatch(): void
    {
        $TEST_ID = 'test13';
        $create_same_id_element = static function (string $order) use ($TEST_ID): HTMLDivElement {
            $element = self::$document->createElement('div');
            $element->setAttribute('id', $TEST_ID);
            $element->setAttribute('data-order', $order);

            return $element;
        };

        $container = self::$document->createElement('div');
        $container->setAttribute('id', $TEST_ID . '-fixture');
        self::$gBody->appendChild($container);

        $element1 = $create_same_id_element('1');
        $element2 = $create_same_id_element('2');
        $element3 = $create_same_id_element('3');
        $element4 = $create_same_id_element('4');

        // append element: 2 -> 4 -> 3 -> 1
        $container->appendChild($element2);
        $container->appendChild($element4);
        $container->insertBefore($element3, $element4);
        $container->insertBefore($element1, $element2);

        $test = self::$document->getElementById($TEST_ID);
        $this->assertSame($element1, $test);
        $container->removeChild($element1);

        $test = self::$document->getElementById($TEST_ID);
        $this->assertSame($element2, $test);
        $container->removeChild($element2);

        $test = self::$document->getElementById($TEST_ID);
        $this->assertSame($element3, $test);
        $container->removeChild($element3);

        $test = self::$document->getElementById($TEST_ID);
        $this->assertSame($element4, $test);
        $container->removeChild($element4);
    }

    public function testInsertingAnIdByInsertingItsParentNode(): void
    {
        $TEST_ID = 'test14';
        $a = self::$document->createElement('a');
        $b = self::$document->createElement('b');
        $a->appendChild($b);
        $b->id = $TEST_ID;
        $this->assertNull(self::$document->getElementById($TEST_ID));

        self::$gBody->appendChild($a);
        $this->assertSame($b, self::$document->getElementById($TEST_ID));
    }

    public function testGetElementByIdMustNotReturnNodesNotPresentInDocument(): void
    {
        $TEST_ID = 'test15';
        $outer = self::$document->getElementById('outer');
        $middle = self::$document->getElementById('middle');
        $inner = self::$document->getElementById('inner');
        $outer->removeChild($middle);

        // the new element is not part of the document since
        // "middle" element was removed previously
        $newEl = self::$document->createElement('h1');
        $newEl->id = 'heading';
        $inner->appendChild($newEl);

        $this->assertNull(self::$document->getElementById('heading'));
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<meta charset=utf-8>
<title>Document.getElementById</title>
<link rel="author" title="Tetsuharu OHZEKI" href="mailto:saneyuki.snyk@gmail.com">
<link rel=help href="https://dom.spec.whatwg.org/#dom-document-getelementbyid">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<body>
    <div id="log"></div>

    <!-- test 0 -->
    <div id=""></div>

    <!-- test 1 -->
    <div id="test1"></div>

    <!-- test 5 -->
    <div id="test5" data-name="1st">
    <p id="test5" data-name="2nd">P</p>
    <input id="test5" type="submit" value="Submit" data-name="3rd">
    </div>

    <!-- test 15 -->
    <div id="outer">
    <div id="middle">
        <div id="inner"></div>
    </div>
    </div>

<script>
    var gBody = document.getElementsByTagName("body")[0];

    test(function() {
    assert_equals(document.getElementById(""), null);
    }, "Calling document.getElementById with an empty string argument.");

    test(function() {
    var element = document.createElement("div");
    element.setAttribute("id", "null");
    document.body.appendChild(element);
    this.add_cleanup(function() { document.body.removeChild(element) });
    assert_equals(document.getElementById(null), element);
    }, "Calling document.getElementById with a null argument.");

    test(function() {
    var element = document.createElement("div");
    element.setAttribute("id", "undefined");
    document.body.appendChild(element);
    this.add_cleanup(function() { document.body.removeChild(element) });
    assert_equals(document.getElementById(undefined), element);
    }, "Calling document.getElementById with an undefined argument.");


    test(function() {
    var bar = document.getElementById("test1");
    assert_not_equals(bar, null, "should not be null");
    assert_equals(bar.tagName, "DIV", "should have expected tag name.");
    assert_true(bar instanceof HTMLDivElement, "should be a valid Element instance");
    }, "on static page");


    test(function() {
    var TEST_ID = "test2";

    var test = document.createElement("div");
    test.setAttribute("id", TEST_ID);
    gBody.appendChild(test);

    // test: appended element
    var result = document.getElementById(TEST_ID);
    assert_not_equals(result, null, "should not be null.");
    assert_equals(result.tagName, "DIV", "should have appended element's tag name");
    assert_true(result instanceof HTMLDivElement, "should be a valid Element instance");

    // test: removed element
    gBody.removeChild(test);
    var removed = document.getElementById(TEST_ID);
    // `document.getElementById()` returns `null` if there is none.
    // https://dom.spec.whatwg.org/#dom-nonelementparentnode-getelementbyid
    assert_equals(removed, null, "should not get removed element.");
    }, "Document.getElementById with a script-inserted element");


    test(function() {
    // setup fixtures.
    var TEST_ID = "test3";
    var test = document.createElement("div");
    test.setAttribute("id", TEST_ID);
    gBody.appendChild(test);

    // update id
    var UPDATED_ID = "test3-updated";
    test.setAttribute("id", UPDATED_ID);
    var e = document.getElementById(UPDATED_ID);
    assert_equals(e, test, "should get the element with id.");

    var old = document.getElementById(TEST_ID);
    assert_equals(old, null, "shouldn't get the element by the old id.");

    // remove id.
    test.removeAttribute("id");
    var e2 = document.getElementById(UPDATED_ID);
    assert_equals(e2, null, "should return null when the passed id is none in document.");
    }, "update `id` attribute via setAttribute/removeAttribute");


    test(function() {
    var TEST_ID = "test4-should-not-exist";

    var e = document.createElement('div');
    e.setAttribute("id", TEST_ID);

    assert_equals(document.getElementById(TEST_ID), null, "should be null");
    document.body.appendChild(e);
    assert_equals(document.getElementById(TEST_ID), e, "should be the appended element");
    }, "Ensure that the id attribute only affects elements present in a document");


    test(function() {
    // the method should return the 1st element.
    var TEST_ID = "test5";
    var target = document.getElementById(TEST_ID);
    assert_not_equals(target, null, "should not be null");
    assert_equals(target.getAttribute("data-name"), "1st", "should return the 1st");

    // even if after the new element was appended.
    var element4 = document.createElement("div");
    element4.setAttribute("id", TEST_ID);
    element4.setAttribute("data-name", "4th");
    gBody.appendChild(element4);
    var target2 = document.getElementById(TEST_ID);
    assert_not_equals(target2, null, "should not be null");
    assert_equals(target2.getAttribute("data-name"), "1st", "should be the 1st");

    // should return the next element after removed the subtree including the 1st element.
    target2.parentNode.removeChild(target2);
    var target3 = document.getElementById(TEST_ID);
    assert_not_equals(target3, null, "should not be null");
    assert_equals(target3.getAttribute("data-name"), "4th", "should be the 4th");
    }, "in tree order, within the context object's tree");


    test(function() {
    var TEST_ID = "test6";
    var s = document.createElement("div");
    s.setAttribute("id", TEST_ID);
    // append to Element, not Document.
    document.createElement("div").appendChild(s);

    assert_equals(document.getElementById(TEST_ID), null, "should be null");
    }, "Modern browsers optimize this method with using internal id cache. " +
        "This test checks that their optimization should effect only append to `Document`, not append to `Node`.");


    test(function() {
    var TEST_ID = "test7"
    var element = document.createElement("div");
    element.setAttribute("id", TEST_ID);
    gBody.appendChild(element);

    var target = document.getElementById(TEST_ID);
    assert_equals(target, element, "should return the element before changing the value");

    element.attributes[0].value = TEST_ID + "-updated";
    var target2 = document.getElementById(TEST_ID);
    assert_equals(target2, null, "should return null after updated id via Attr.value");
    var target3 = document.getElementById(TEST_ID + "-updated");
    assert_equals(target3, element, "should be equal to the updated element.");
    }, "changing attribute's value via `Attr` gotten from `Element.attribute`.");


    test(function() {
    var TEST_ID = "test8";

    // setup fixture
    var element = document.createElement("div");
    element.setAttribute("id", TEST_ID + "-fixture");
    gBody.appendChild(element);

    // add id-ed element with using innerHTML
    element.innerHTML = "<div id='"+ TEST_ID +"'></div>";
    var test = document.getElementById(TEST_ID);
    assert_equals(test, element.firstChild, "should not be null");
    assert_equals(test.tagName, "DIV", "should have expected tag name.");
    assert_true(test instanceof HTMLDivElement, "should be a valid Element instance");
    }, "add id attribute via innerHTML");


    test(function() {
    var TEST_ID = "test9";

    // add fixture
    var fixture = document.createElement("div");
    fixture.setAttribute("id", TEST_ID + "-fixture");
    gBody.appendChild(fixture);

    var element = document.createElement("div");
    element.setAttribute("id", TEST_ID);
    fixture.appendChild(element);

    // check 'getElementById' should get the 'element'
    assert_equals(document.getElementById(TEST_ID), element, "should not be null");

    // remove id-ed element with using innerHTML (clear 'element')
    fixture.innerHTML = "";
    var test = document.getElementById(TEST_ID);
    assert_equals(test, null, "should be null.");
    }, "remove id attribute via innerHTML");


    test(function() {
    var TEST_ID = "test10";

    // setup fixture
    var element = document.createElement("div");
    element.setAttribute("id", TEST_ID + "-fixture");
    gBody.appendChild(element);

    // add id-ed element with using outerHTML
    element.outerHTML = "<div id='"+ TEST_ID +"'></div>";
    var test = document.getElementById(TEST_ID);
    assert_not_equals(test, null, "should not be null");
    assert_equals(test.tagName, "DIV", "should have expected tag name.");
    assert_true(test instanceof HTMLDivElement,"should be a valid Element instance");
    }, "add id attribute via outerHTML");


    test(function() {
    var TEST_ID = "test11";

    var element = document.createElement("div");
    element.setAttribute("id", TEST_ID);
    gBody.appendChild(element);

    var test = document.getElementById(TEST_ID);
    assert_equals(test, element, "should be equal to the appended element.");

    // remove id-ed element with using outerHTML
    element.outerHTML = "<div></div>";
    var test = document.getElementById(TEST_ID);
    assert_equals(test, null, "should be null.");
    }, "remove id attribute via outerHTML");


    test(function() {
    // setup fixtures.
    var TEST_ID = "test12";
    var test = document.createElement("div");
    test.id = TEST_ID;
    gBody.appendChild(test);

    // update id
    var UPDATED_ID = TEST_ID + "-updated";
    test.id =  UPDATED_ID;
    var e = document.getElementById(UPDATED_ID);
    assert_equals(e, test, "should get the element with id.");

    var old = document.getElementById(TEST_ID);
    assert_equals(old, null, "shouldn't get the element by the old id.");

    // remove id.
    test.id = "";
    var e2 = document.getElementById(UPDATED_ID);
    assert_equals(e2, null, "should return null when the passed id is none in document.");
    }, "update `id` attribute via element.id");


    test(function() {
    var TEST_ID = "test13";

    var create_same_id_element = function (order) {
        var element = document.createElement("div");
        element.setAttribute("id", TEST_ID);
        element.setAttribute("data-order", order);// for debug
        return element;
    };

    // create fixture
    var container = document.createElement("div");
    container.setAttribute("id", TEST_ID + "-fixture");
    gBody.appendChild(container);

    var element1 = create_same_id_element("1");
    var element2 = create_same_id_element("2");
    var element3 = create_same_id_element("3");
    var element4 = create_same_id_element("4");

    // append element: 2 -> 4 -> 3 -> 1
    container.appendChild(element2);
    container.appendChild(element4);
    container.insertBefore(element3, element4);
    container.insertBefore(element1, element2);


    var test = document.getElementById(TEST_ID);
    assert_equals(test, element1, "should return 1st element");
    container.removeChild(element1);

    test = document.getElementById(TEST_ID);
    assert_equals(test, element2, "should return 2nd element");
    container.removeChild(element2);

    test = document.getElementById(TEST_ID);
    assert_equals(test, element3, "should return 3rd element");
    container.removeChild(element3);

    test = document.getElementById(TEST_ID);
    assert_equals(test, element4, "should return 4th element");
    container.removeChild(element4);


    }, "where insertion order and tree order don't match");

    test(function() {
    var TEST_ID = "test14";
    var a = document.createElement("a");
    var b = document.createElement("b");
    a.appendChild(b);
    b.id = TEST_ID;
    assert_equals(document.getElementById(TEST_ID), null);

    gBody.appendChild(a);
    assert_equals(document.getElementById(TEST_ID), b);
    }, "Inserting an id by inserting its parent node");

    test(function () {
    var TEST_ID = "test15"
    var outer = document.getElementById("outer");
    var middle = document.getElementById("middle");
    var inner = document.getElementById("inner");
    outer.removeChild(middle);

    var new_el = document.createElement("h1");
    new_el.id = "heading";
    inner.appendChild(new_el);
    // the new element is not part of the document since
    // "middle" element was removed previously
    assert_equals(document.getElementById("heading"), null);
    }, "Document.getElementById must not return nodes not present in document");

    // TODO:
    // id attribute in a namespace


    // TODO:
    // SVG + MathML elements with id attributes

</script>
</body>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
        self::$gBody = self::$document->getElementsByTagName('body')[0];
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$gBody = null;
    }
}
