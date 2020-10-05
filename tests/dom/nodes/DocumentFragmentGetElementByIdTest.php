<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DocumentFragment;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/DocumentFragment-getElementById.html
 */
class DocumentFragmentGetElementByIdTest extends TestCase
{
    private static $document;

    public function testMethodMustExist(): void
    {
        $this->assertTrue(method_exists(DocumentFragment::class, 'getElementById'));
    }

    public function testMustReturnNullWhenThereAreNoMatches(): void
    {
        $this->assertNull(self::$document->createDocumentFragment()->getElementById('foo'));
        $this->assertNull(self::$document->createDocumentFragment()->getElementById(''));
    }

    public function testMustReturnFirstElementWhenThereAreMatches(): void
    {
        $frag = self::$document->createDocumentFragment();
        $frag->appendChild(self::$document->createElement('div'));
        $frag->appendChild(self::$document->createElement('span'));
        $frag->childNodes[0]->id = 'foo';
        $frag->childNodes[1]->id = 'foo';

        $this->assertSame($frag->childNodes[0], $frag->getElementById('foo'));
    }

    public function testEmptyStringIdValues(): void
    {
        $frag = self::$document->createDocumentFragment();
        $frag->appendChild(self::$document->createElement('div'));
        $frag->childNodes[0]->setAttribute('id', '');

        $this->assertNull($frag->getElementById(''));
    }

    public function testMustReturnFirstElementWhenThereAreMatchesUsingTemplate(): void
    {
        //$frag = self::$document->querySelector('template')->content;
        $frag = self::$document->getElementsByTagName('template')[0]->content;

        $this->assertTrue($frag->getElementById('foo')->hasAttribute('data-yes'));
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<meta charset="utf-8">
<title>DocumentFragment.prototype.getElementById</title>
<link rel="help" href="https://dom.spec.whatwg.org/#dom-nonelementparentnode-getelementbyid">
<link rel="author" title="Domenic Denicola" href="mailto:d@domenic.me">

<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>

<template>
    <div id="bar">
    <span id="foo" data-yes></span>
    </div>
    <div id="foo">
    <span id="foo"></span>
    <ul id="bar">
        <li id="foo"></li>
    </ul>
    </div>
</template>

<script>
"use strict";

test(() => {
    assert_equals(typeof DocumentFragment.prototype.getElementById, "function", "It must exist on the prototype");
    assert_equals(typeof document.createDocumentFragment().getElementById, "function", "It must exist on an instance");
}, "The method must exist");

test(() => {
    assert_equals(document.createDocumentFragment().getElementById("foo"), null);
    assert_equals(document.createDocumentFragment().getElementById(""), null);
}, "It must return null when there are no matches");

test(() => {
    const frag = document.createDocumentFragment();
    frag.appendChild(document.createElement("div"));
    frag.appendChild(document.createElement("span"));
    frag.childNodes[0].id = "foo";
    frag.childNodes[1].id = "foo";

    assert_equals(frag.getElementById("foo"), frag.childNodes[0]);
}, "It must return the first element when there are matches");

test(() => {
    const frag = document.createDocumentFragment();
    frag.appendChild(document.createElement("div"));
    frag.childNodes[0].setAttribute("id", "");

    assert_equals(
    frag.getElementById(""),
    null,
    "Even if there is an element with an empty-string ID attribute, it must not be returned"
    );
}, "Empty string ID values");

test(() => {
    const frag = document.querySelector("template").content;

    assert_true(frag.getElementById("foo").hasAttribute("data-yes"));
}, "It must return the first element when there are matches, using a template");
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
