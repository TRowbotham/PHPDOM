<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-hasAttribute.html
 */
class ElementHasAttributeTest extends TestCase
{
    private static $document;

    public function testHasAttributeShouldCheckForAttribuePresenceIrrespectiveOfNamespace(): void
    {
        $el = self::$document->createElement('p');
        $el->setAttributeNS('foo', 'x', 'first');

        $this->assertTrue($el->hasAttribute('x'));
    }

    public function testHasAttributeShouldWorkWithAllAttributeCasings(): void
    {
        $el = self::$document->getElementById('t');

        $this->assertTrue($el->hasAttribute('data-e2'));
        $this->assertTrue($el->hasAttribute('data-E2'));
        $this->assertTrue($el->hasAttribute('data-f2'));
        $this->assertTrue($el->hasAttribute('data-F2'));
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<meta charset="utf-8">
<title>Element.prototype.hasAttribute</title>
<link rel=help href="https://dom.spec.whatwg.org/#dom-element-hasattribute">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>

<span data-e2="2" data-F2="3" id="t"></span>

<script>
"use strict";

test(() => {

    const el = document.createElement("p");
    el.setAttributeNS("foo", "x", "first");

    assert_true(el.hasAttribute("x"));

}, "hasAttribute should check for attribute presence, irrespective of namespace");

test(() => {

    const el = document.getElementById("t");

    assert_true(el.hasAttribute("data-e2"));
    assert_true(el.hasAttribute("data-E2"));
    assert_true(el.hasAttribute("data-f2"));
    assert_true(el.hasAttribute("data-F2"));

}, "hasAttribute should work with all attribute casings");
</script>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
