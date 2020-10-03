<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-childElementCount-dynamic-remove.html
 */
class ElementChildElementCountDynamicRemoveHtmlTest extends TestCase
{
    public function testElementChildElementCount(): void
    {
        $document = self::loadDocument();
        $parentEl = $document->getElementById('parentEl');
        $lec = $parentEl->lastElementChild;
        $parentEl->removeChild($lec);

        $this->assertSame(1, $parentEl->childElementCount);
    }

    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE HTML>
<meta charset=utf-8>
<title>Dynamic Removal of Elements</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<h1>Test of Dynamic Removal of Elements</h1>
<div id="log"></div>
<p id="parentEl">The result of this test is
<span id="first_element_child" style="font-weight:bold;">unknown.</span><span id="last_element_child"> </span></p>
<script>
test(function() {
    var parentEl = document.getElementById("parentEl");
    var lec = parentEl.lastElementChild;
    parentEl.removeChild(lec);
    assert_equals(parentEl.childElementCount, 1)
})
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
