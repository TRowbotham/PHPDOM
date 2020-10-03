<?php

namespace Rowbot\DOM\Tests\dom\lists;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\traversal\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/lists/DOMTokenList-Iterable.html
 */
class DOMTokenListIterableTest extends TestCase
{
    public function testDOMTokenListForeach(): void
    {
        $classList = [];
        // $elementClasses = self::loadDocument()->querySelector('span')->classList;
        $elementClasses = self::loadDocument()->getElementsByTagName('span')[0]->classList;

        foreach ($elementClasses as $className) {
            $classList[] = $className;
        }

        $this->assertSame(['foo', 'Foo'], $classList);
    }

    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<meta charset="utf-8">
<title>DOMTokenList Iterable Test</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<span class="foo   Foo foo   "></span>
<script>
    var elementClasses;
    setup(function() {
        elementClasses = document.querySelector("span").classList;
    })
    test(function() {
        assert_true('length' in elementClasses);
    }, 'DOMTokenList has length method.');
    test(function() {
        assert_true('values' in elementClasses);
    }, 'DOMTokenList has values method.');
    test(function() {
        assert_true('entries' in elementClasses);
    }, 'DOMTokenList has entries method.');
    test(function() {
        assert_true('forEach' in elementClasses);
    }, 'DOMTokenList has forEach method.');
    test(function() {
        assert_true(Symbol.iterator in elementClasses);
    }, 'DOMTokenList has Symbol.iterator.');
    test(function() {
        var classList = [];
        for (var className of elementClasses){
            classList.push(className);
        }
        assert_array_equals(classList, ['foo', 'Foo']);
    }, 'DOMTokenList is iterable via for-of loop.');
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
