<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-hasAttributes.html
 */
class ElementHasAttributesTest extends TestCase
{
    private static $document;

    public function testHasAttributesMustReturnFalseWhenTheElementDoesNotHaveAnAttribute(): void
    {
        $buttonElement = self::$document->getElementsByTagName('button')[0];
        $this->assertFalse($buttonElement->hasAttributes());

        $emptyDiv = self::$document->createElement('div');
        $this->assertFalse($emptyDiv->hasAttributes());
    }

    public function testHasAttributesMustReturnTrueWhenTheElementHasAttributes(): void
    {
        $divWithId = self::$document->getElementById('foo');
        $this->assertTrue($divWithId->hasAttributes());

        $divWithClass = self::$document->createElement('div');
        $divWithClass->setAttribute('class', 'foo');
        $this->assertTrue($divWithClass->hasAttributes());

        $pWithCustomAttr = self::$document->getElementsByTagName('p')[0];
        $this->assertTrue($pWithCustomAttr->hasAttributes());

        $divWithCustomAttr = self::$document->createElement('div');
        $divWithCustomAttr->setAttribute('data-custom', 'foo');
        $this->assertTrue($divWithCustomAttr->hasAttributes());
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<meta charset="utf-8">
<title></title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<body>

<button></button>
<div id="foo"></div>
<p data-foo=""></p>

<script>
test(function() {
    var buttonElement = document.getElementsByTagName('button')[0];
    assert_equals(buttonElement.hasAttributes(), false, 'hasAttributes() on empty element must return false.');

    var emptyDiv = document.createElement('div');
    assert_equals(emptyDiv.hasAttributes(), false, 'hasAttributes() on dynamically created empty element must return false.');

}, 'element.hasAttributes() must return false when the element does not have attribute.');

test(function() {
    var divWithId = document.getElementById('foo');
    assert_equals(divWithId.hasAttributes(), true, 'hasAttributes() on element with id attribute must return true.');

    var divWithClass = document.createElement('div');
    divWithClass.setAttribute('class', 'foo');
    assert_equals(divWithClass.hasAttributes(), true, 'hasAttributes() on dynamically created element with class attribute must return true.');

    var pWithCustomAttr = document.getElementsByTagName('p')[0];
    assert_equals(pWithCustomAttr.hasAttributes(), true, 'hasAttributes() on element with custom attribute must return true.');

    var divWithCustomAttr = document.createElement('div');
    divWithCustomAttr.setAttribute('data-custom', 'foo');
    assert_equals(divWithCustomAttr.hasAttributes(), true, 'hasAttributes() on dynamically created element with custom attribute must return true.');

}, 'element.hasAttributes() must return true when the element has attribute.');

</script>
</body>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
