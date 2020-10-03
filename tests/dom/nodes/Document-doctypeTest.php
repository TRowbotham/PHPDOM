<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-doctype.html
 */
class DocumentDoctypeTest extends TestCase
{
    public function testWindowWithDoctpye(): void
    {
        $document = self::loadDocument();

        $this->assertInstanceOf(DocumentType::class, $document->doctype);
        $this->assertSame($document->childNodes[1], $document->doctype);
    }

    public function testNewDocument(): void
    {
        $newdoc = new Document();
        $newdoc->appendChild($newdoc->createElement('html'));

        $this->assertNull($newdoc->doctype);
    }
    public static function loadDocument(): HTMLDocument
    {
        $html = <<<'TEST_HTML'
<!-- comment -->
<!doctype html>
<meta charset=utf-8>
<title>Document.doctype</title>
<link rel=help href="https://dom.spec.whatwg.org/#dom-document-doctype">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<div id="log"></div>
<script>
test(function() {
    assert_true(document.doctype instanceof DocumentType,
                "Doctype should be a DocumentType");
    assert_equals(document.doctype, document.childNodes[1]);
}, "Window document with doctype");

test(function() {
    var newdoc = new Document();
    newdoc.appendChild(newdoc.createElement("html"));
    assert_equals(newdoc.doctype, null);
}, "new Document()");
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
