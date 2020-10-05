<?php

namespace Rowbot\DOM\Tests\dom\lists;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\traversal\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/lists/DOMTokenList-value.html
 */
class DOMTokenListValueTest extends TestCase
{
    public function testValue(): void
    {
        $document = self::loadDocument();

        $this->assertSame('', (string) $document->createElement('span')->classList->value);

        // $span = $document->querySelector('span');
        $span = $document->getElementsByTagName('span')[0];

        $this->assertSame("   a  a b ", $span->classList->value);
        $span->classList->value = " foo bar foo ";
        $this->assertSame(" foo bar foo ", $span->classList->value);
        $this->assertSame(2, $span->classList->length);
    }

    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<meta charset="utf-8">
<title>DOMTokenList value</title>
<link rel=help href="https://dom.spec.whatwg.org/#dom-domtokenlist-value">
<link rel=author title=Tangresh href="mailto:dmenzi@tangresh.ch">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<span class="   a  a b "></span>
<script>
test(function() {
    assert_equals(String(document.createElement("span").classList.value), "",
                "classList.value should return the empty list for an undefined class attribute");
    var span = document.querySelector("span");
    assert_equals(span.classList.value, "   a  a b ",
                "value should return the literal value");
    span.classList.value = " foo bar foo ";
    assert_equals(span.classList.value, " foo bar foo ",
                "assigning value should set the literal value");
    assert_equals(span.classList.length, 2,
                "length should be the number of tokens");
    assert_class_string(span.classList, "DOMTokenList");
    assert_class_string(span.classList.value, "String");
});
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
