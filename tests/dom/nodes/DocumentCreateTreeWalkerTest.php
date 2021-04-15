<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\dom\traversal\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createTreeWalker.html
 */
class DocumentCreateTreeWalkerTest extends TestCase
{
    private static $document;

    public function testCreateTreeWalkerOptionalArguments1(): void
    {
        $tw = self::$document->createTreeWalker(self::$document->body);

        $this->assertSame(self::$document->body, $tw->root);
        $this->assertSame(self::$document->body, $tw->currentNode);
        $this->assertSame(0xFFFFFFFF, $tw->whatToShow);
        $this->assertNull($tw->filter);
    }

    public function testCreateTreeWalkerOptionalArguments2(): void
    {
        $tw = self::$document->createTreeWalker(self::$document->body, 42);

        $this->assertSame(self::$document->body, $tw->root);
        $this->assertSame(self::$document->body, $tw->currentNode);
        $this->assertSame(42, $tw->whatToShow);
        $this->assertNull($tw->filter);
    }

    public function testCreateTreeWalkerOptionalArguments3(): void
    {
        $tw = self::$document->createTreeWalker(self::$document->body, 42, null);

        $this->assertSame(self::$document->body, $tw->root);
        $this->assertSame(self::$document->body, $tw->currentNode);
        $this->assertSame(42, $tw->whatToShow);
        $this->assertNull($tw->filter);
    }

    public function testCreateTreeWalkerOptionalArguments3Filter(): void
    {
        $fn = static function () {
        };
        $tw = self::$document->createTreeWalker(self::$document->body, 42, $fn);

        $this->assertSame(self::$document->body, $tw->root);
        $this->assertSame(self::$document->body, $tw->currentNode);
        $this->assertSame(42, $tw->whatToShow);
        $this->assertSame($fn, $tw->filter);
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<meta charset=utf-8>
<title>Document.createTreeWalker</title>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<div id=log></div>
<script>
test(function() {
    assert_throws_js(TypeError, function() {
    document.createTreeWalker();
    });
}, "Required arguments to createTreeWalker should be required.");
test(function() {
    var tw = document.createTreeWalker(document.body);
    assert_equals(tw.root, document.body);
    assert_equals(tw.currentNode, document.body);
    assert_equals(tw.whatToShow, 0xFFFFFFFF);
    assert_equals(tw.filter, null);
}, "Optional arguments to createTreeWalker should be optional (1 passed).");
test(function() {
    var tw = document.createTreeWalker(document.body, 42);
    assert_equals(tw.root, document.body);
    assert_equals(tw.currentNode, document.body);
    assert_equals(tw.whatToShow, 42);
    assert_equals(tw.filter, null);
}, "Optional arguments to createTreeWalker should be optional (2 passed).");
test(function() {
    var tw = document.createTreeWalker(document.body, 42, null);
    assert_equals(tw.root, document.body);
    assert_equals(tw.currentNode, document.body);
    assert_equals(tw.whatToShow, 42);
    assert_equals(tw.filter, null);
}, "Optional arguments to createTreeWalker should be optional (3 passed, null).");
test(function() {
    var fn = function() {};
    var tw = document.createTreeWalker(document.body, 42, fn);
    assert_equals(tw.root, document.body);
    assert_equals(tw.currentNode, document.body);
    assert_equals(tw.whatToShow, 42);
    assert_equals(tw.filter, fn);
}, "Optional arguments to createTreeWalker should be optional (3 passed, function).");
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
