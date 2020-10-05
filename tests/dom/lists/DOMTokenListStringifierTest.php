<?php

namespace Rowbot\DOM\Tests\dom\lists;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\traversal\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/lists/DOMTokenList-stringifier.html
 */
class DOMTokenListStringifierTest extends TestCase
{
    public function testStringifier(): void
    {
        $document = self::loadDocument();

        $this->assertSame('', (string) $document->createElement('span')->classList);

        // $span = $document->querySelector('span');
        $span = $document->getElementsByTagName('span')[0];

        $this->assertSame("   a  a b ", $span->getAttribute('class'));
        $this->assertSame("   a  a b ", $span->className);
        $this->assertSame("   a  a b ", (string) $span->classList);
        $this->assertSame("   a  a b ", $span->classList->toString());
    }

    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<meta charset=utf-8>
<title>DOMTokenList stringifier</title>
<link rel=help href="https://dom.spec.whatwg.org/#dom-domtokenlist-stringifier">
<link rel=author title=Ms2ger href="mailto:Ms2ger@gmail.com">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<div id=log></div>
<span class="   a  a b "></span>
<script>
test(function() {
    assert_equals(String(document.createElement("span").classList), "",
                "String(classList) should return the empty list for an undefined class attribute");
    var span = document.querySelector("span");
    assert_equals(span.getAttribute("class"), "   a  a b ",
                "getAttribute should return the literal value");
    assert_equals(span.className, "   a  a b ",
                "className should return the literal value");
    assert_equals(String(span.classList), "   a  a b ",
                "String(classList) should return the literal value");
    assert_equals(span.classList.toString(), "   a  a b ",
                "classList.toString() should return the literal value");
    assert_class_string(span.classList, "DOMTokenList");
});
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
