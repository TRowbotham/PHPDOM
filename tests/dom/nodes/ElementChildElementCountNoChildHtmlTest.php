<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-childElementCount-nochild.html
 */
class ElementChildElementCountNoChildHtmlTest extends TestCase
{
    public function testElementChildElementCount(): void
    {
        $document = self::loadDocument();
        $parentEl = $document->getElementById('parentEl');

        $this->assertSame(0, $parentEl->childElementCount);
    }

    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE HTML>
<meta charset=utf-8>
<title>childElementCount without Child Element Nodes</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<h1>Test of childElementCount with No Child Element Nodes</h1>
<div id="log"></div>
<p id="parentEl" style="font-weight:bold;">Test.</p>
<script>
test(function() {
    var parentEl = document.getElementById("parentEl")
    assert_equals(parentEl.childElementCount, 0)
})
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
