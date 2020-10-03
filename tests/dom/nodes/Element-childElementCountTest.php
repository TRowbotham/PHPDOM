<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-childElementCount.html
 */
class ElementChildElementCountTest extends TestCase
{
    public function testElementChildElementCount(): void
    {
        $document = self::loadDocument();
        $parentEl = $document->getElementById('parentEl');

        $this->assertSame(3, $parentEl->childElementCount);
    }

    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE HTML>
<meta charset=utf-8>
<title>childElementCount</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<h1>Test of childElementCount</h1>
<div id="log"></div>
<p id="parentEl">The result of <span id="first_element_child"><span>this</span> <span>test</span></span> is
<span id="middle_element_child" style="font-weight:bold;">given above.</span>



<span id="last_element_child" style="display:none;">fnord</span> </p>
<script>
test(function() {
    var parentEl = document.getElementById("parentEl")
    assert_true("childElementCount" in parentEl)
    assert_equals(parentEl.childElementCount, 3)
})
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
