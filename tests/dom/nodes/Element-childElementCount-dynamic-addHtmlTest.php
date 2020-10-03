<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-childElementCount-dynamic-add.html
 */
class ElementChildElementCountDynamicAddHtmlTest extends TestCase
{
    public function testElementChildElementCount(): void
    {
        $document = self::loadDocument();
        $parentEl = $document->getElementById('parentEl');
        $newChild = $document->createElement('span');
        $parentEl->appendChild($newChild);

        $this->assertSame(2, $parentEl->childElementCount);
    }

    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE HTML>
<meta charset=utf-8>
<title>Dynamic Adding of Elements</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<h1>Test of Dynamic Adding of Elements</h1>
<div id="log"></div>
<p id="parentEl">The result of this test is
<span id="first_element_child" style="font-weight:bold;">logged above.</span></p>
<script>
test(function() {
    var parentEl = document.getElementById("parentEl");
    var newChild = document.createElement("span");
    parentEl.appendChild(newChild);
    assert_equals(parentEl.childElementCount, 2)
})
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
