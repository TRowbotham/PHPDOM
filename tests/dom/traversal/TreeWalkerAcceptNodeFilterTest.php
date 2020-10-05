<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Closure;
use Exception;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker-acceptNode-filter.html
 */
class TreeWalkerAcceptNodeFilterTest extends TestCase
{
    private static $document;
    private static $testElement;

    public function testWithRawFunctionFilter(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ALL,
            static function (Node $node): int {
                if ($node->id === 'B1') {
                    return NodeFilter::FILTER_SKIP;
                }

                return NodeFilter::FILTER_ACCEPT;
            }
        );

        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->currentNode);
    }

    public function testWithObjectFilter(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ALL,
            new class implements NodeFilter {
                public function acceptNode(Node $node): int
                {
                    if ($node->id === 'B1') {
                        return NodeFilter::FILTER_SKIP;
                    }

                    return NodeFilter::FILTER_ACCEPT;
                }
            }
        );

        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B2'], $walker->currentNode);
    }

    public function testWithNullFilter(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            null
        );

        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->currentNode);
    }

    public function testWithUndefinedFilter(): void
    {
        $walker = self::$document->createTreeWalker(self::$testElement, NodeFilter::SHOW_ELEMENT);

        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->currentNode);
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->nextNode());
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->currentNode);
    }

    public function testWithObjectLackingAcceptNodeProperty(): void
    {
        $this->markTestIncomplete('We don\'t properly handle a filter that is not valid.');
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            new class {}
        );

        $this->assertThrows(static function () use ($walker): void {
            $walker->firstChild();
        }, TypeError::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertThrows(static function () use ($walker): void {
            $walker->nextNode();
        }, TypeError::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
    }

    public function testWithObjectWithNonFunctionAcceptNodeProperty(): void
    {
        $this->markTestIncomplete('We don\'t support call indirection from "call a user object\'s operation".');
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            new class {
                public $acceptNode = 'foo';
            }
        );

        $this->assertThrows(static function () use ($walker): void {
            $walker->firstChild();
        }, TypeError::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertThrows(static function () use ($walker): void {
            $walker->nextNode();
        }, TypeError::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
    }

    public function testWithFunctionHavingAcceptNodeFunction(): void
    {
        $this->markTestSkipped('Not possible to represent the filter input.');
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            static function (): int {
                return NodeFilter::FILTER_ACCEPT;
            }
        );

        $this->assertNode(['type' => Element::class, 'id' => 'A1'], $walker->firstChild());
        $this->assertNode(['type' => Element::class, 'id' => 'B1'], $walker->nextNode());
    }

    public function testWithFilterFunctionThatThrows(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            static function (): void {
                throw new Exception();
            }
        );

        $this->assertThrows(static function () use ($walker): void {
            $walker->firstChild();
        }, Exception::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertThrows(static function () use ($walker): void {
            $walker->nextNode();
        }, Exception::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
    }

    public function testRethrowsErrorsWhenGettingAcceptNode(): void
    {
        $this->markTestIncomplete('We don\'t support call indirection from "call a user object\'s operation".');
        $filter = new class {
            public $acceptNode;

            public function __construct()
            {
                $this->acceptNode = static function (): void {
                    throw new Exception();
                };
            }
        };
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            $filter
        );

        $this->assertThrows(static function () use ($walker): void {
            $walker->firstChild();
        }, Exception::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertThrows(static function () use ($walker): void {
            $walker->nextNode();
        }, Exception::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
    }

    public function testPerformsGetOnEveryTraverse(): void
    {
        $this->markTestIncomplete('We don\'t support call indirection from "call a user object\'s operation".');
        $calls = 0;
        $filter = new class ($calls) {
            public $acceptNode;

            public function __construct(int &$calls)
            {
                $this->acceptNode = static function () use (&$calls): Closure {
                    ++$calls;

                    return static function (): int {
                        return NodeFilter::FILTER_ACCEPT;
                    };
                };
            }
        };
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            $filter
        );

        $this->assertSame(0, $calls);
        $walker->nextNode();
        $walker->nextNode();
        $this->assertSame(2, $calls);
    }

    public function testWithFilterObjectThatThrows(): void
    {
        $walker = self::$document->createTreeWalker(
            self::$testElement,
            NodeFilter::SHOW_ELEMENT,
            new class implements NodeFilter {
                public function acceptNode(Node $node): int
                {
                    throw new Exception();
                }
            }
        );

        $this->assertThrows(static function () use ($walker): void {
            $walker->firstChild();
        }, Exception::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
        $this->assertThrows(static function () use ($walker): void {
            $walker->nextNode();
        }, Exception::class);
        $this->assertNode(['type' => Element::class, 'id' => 'root'], $walker->currentNode);
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<html>
<!--
Test adapted from https://dxr.mozilla.org/chromium/source/src/third_party/WebKit/LayoutTests/fast/dom/TreeWalker/script-tests/acceptNode-filter.js
-->
<head>
<title>TreeWalker: acceptNode-filter</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<script src="support/assert-node.js"></script>
<link rel="help" href="https://dom.spec.whatwg.org/#callbackdef-nodefilter">
<div id=log></div>
</head>
<body>
<p>Test JS objects as NodeFilters</p>
<script>
var testElement;
setup(function() {
    testElement = document.createElement("div");
    testElement.id = 'root';
    //testElement.innerHTML='<div id="A1"><div id="B1"></div><div id="B2"></div></div>';

    // XXX for Servo, build the tree without using innerHTML
    var a1 = document.createElement("div");
    a1.id = "A1";
    var b1 = document.createElement("div");
    b1.id = "B1";
    var b2 = document.createElement("div");
    b2.id = "B2";
    testElement.appendChild(a1);
    a1.appendChild(b1);
    a1.appendChild(b2);
});

test(function()
{
    function filter(node)
    {
        if (node.id == "B1")
            return NodeFilter.FILTER_SKIP;
        return NodeFilter.FILTER_ACCEPT;
    }

    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, filter);
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.currentNode, { type: Element, id: 'A1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B2' });
    assert_node(walker.currentNode, { type: Element, id: 'B2' });
}, 'Testing with raw function filter');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, {
        acceptNode : function(node) {
            if (node.id == "B1")
                return NodeFilter.FILTER_SKIP;
            return NodeFilter.FILTER_ACCEPT;
        }
    });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.currentNode, { type: Element, id: 'A1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B2' });
    assert_node(walker.currentNode, { type: Element, id: 'B2' });
}, 'Testing with object filter');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, null);
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.currentNode, { type: Element, id: 'A1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B1' });
    assert_node(walker.currentNode, { type: Element, id: 'B1' });
}, 'Testing with null filter');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, undefined);
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.currentNode, { type: Element, id: 'A1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B1' });
    assert_node(walker.currentNode, { type: Element, id: 'B1' });
}, 'Testing with undefined filter');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, {});
    assert_throws_js(TypeError, function () { walker.firstChild(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_throws_js(TypeError, function () { walker.nextNode(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
}, 'Testing with object lacking acceptNode property');

test(function()
{
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, { acceptNode: "foo" });
    assert_throws_js(TypeError, function () { walker.firstChild(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_throws_js(TypeError, function () { walker.nextNode(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
}, 'Testing with object with non-function acceptNode property');

test(function(t)
{
    var filter = function() { return NodeFilter.FILTER_ACCEPT; };
    filter.acceptNode = t.unreached_func("`acceptNode` method should not be called on functions");
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, filter);
    assert_node(walker.firstChild(), { type: Element, id: 'A1' });
    assert_node(walker.nextNode(), { type: Element, id: 'B1' });
}, 'Testing with function having acceptNode function');

test(function()
{
    var test_error = { name: "test" };
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT,
                                            function(node) {
                                                throw test_error;
                                            });
    assert_throws_exactly(test_error, function () { walker.firstChild(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_throws_exactly(test_error, function () { walker.nextNode(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
}, 'Testing with filter function that throws');

test(function() {
    var testError = { name: "test" };
    var filter = {
        get acceptNode() {
            throw testError;
        },
    };

    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, filter);
    assert_throws_exactly(testError, function() { walker.firstChild(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_throws_exactly(testError, function() { walker.nextNode(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
}, "rethrows errors when getting `acceptNode`");

test(function() {
    var calls = 0;
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, {
        get acceptNode() {
            calls++;
            return function() {
                return NodeFilter.FILTER_ACCEPT;
            };
        },
    });

    assert_equals(calls, 0);
    walker.nextNode();
    walker.nextNode();
    assert_equals(calls, 2);
}, "performs `Get` on every traverse");

test(function()
{
    var test_error = { name: "test" };
    var walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT,
                                            {
                                                acceptNode : function(node) {
                                                    throw test_error;
                                                }
                                            });
    assert_throws_exactly(test_error, function () { walker.firstChild(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
    assert_throws_exactly(test_error, function () { walker.nextNode(); });
    assert_node(walker.currentNode, { type: Element, id: 'root' });
}, 'Testing with filter object that throws');

test(() =>
{
    let thisValue, nodeArgID;
    const filter = {
        acceptNode(node) {
            thisValue = this;
            nodeArgID = node.id;
            return NodeFilter.FILTER_ACCEPT;
        },
    };

    const walker = document.createTreeWalker(testElement, NodeFilter.SHOW_ELEMENT, filter);
    walker.nextNode();

    assert_equals(thisValue, filter);
    assert_equals(nodeArgID, 'A1');
}, 'Testing with filter object: this value and `node` argument');

</script>
</body>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
        self::$testElement = self::$document->createElement('div');
        self::$testElement->id = 'root';
        self::$testElement->innerHTML = '<div id="A1"><div id="B1"></div><div id="B2"></div></div>';
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$testElement = null;
    }
}
