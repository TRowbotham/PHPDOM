<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-adoptNode.html
 */
class DocumentAdoptNodeTest extends TestCase
{
    private static $document;

    public function testAdoptElement1(): void
    {
        $y = self::$document->getElementsByTagName('x<')[0];
        $child = $y->firstChild;

        $this->assertSame(self::$document->body, $y->parentNode);
        $this->assertSame(self::$document, $y->ownerDocument);
        $this->assertSame($y, self::$document->adoptNode($y));
        $this->assertNull($y->parentNode);
        $this->assertSame($child, $y->firstChild);
        $this->assertSame(self::$document, $y->ownerDocument);
        $this->assertSame(self::$document, $child->ownerDocument);

        $doc = self::$document->implementation->createDocument(null, null, null);

        $this->assertSame($y, $doc->adoptNode($y));
        $this->assertNull($y->parentNode);
        $this->assertSame($child, $y->firstChild);
        $this->assertSame($doc, $y->ownerDocument);
        $this->assertSame($doc, $child->ownerDocument);
    }

    public function testAdoptElement2(): void
    {
        $x = self::$document->createElement(':good:times:');

        $this->assertSame($x, self::$document->adoptNode($x));
        $doc = self::$document->implementation->createDocument(null, null, null);
        $this->assertSame($x, $doc->adoptNode($x));
        $this->assertNull($x->parentNode);
        $this->assertSame($doc, $x->ownerDocument);
    }

    public function testAdoptDocumentType(): void
    {
        $doctype = self::$document->doctype;

        $this->assertSame(self::$document, $doctype->parentNode);
        $this->assertSame(self::$document, $doctype->ownerDocument);
        $this->assertSame($doctype, self::$document->adoptNode($doctype));
        $this->assertNull($doctype->parentNode);
        $this->assertSame(self::$document, $doctype->ownerDocument);
    }

    public function testAdoptingDocumentShouldThrow(): void
    {
        $this->expectException(NotSupportedError::class);
        $doc = self::$document->implementation->createDocument(null, null, null);
        self::$document->adoptNode($doc);
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<meta charset=utf-8>
<title>Document.adoptNode</title>
<link rel=help href="https://dom.spec.whatwg.org/#dom-document-adoptnode">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<div id="log"></div>
<!--creates an element with local name "x<": --><x<>x</x<>
<script>
test(function() {
    var y = document.getElementsByTagName("x<")[0]
    var child = y.firstChild
    assert_equals(y.parentNode, document.body)
    assert_equals(y.ownerDocument, document)
    assert_equals(document.adoptNode(y), y)
    assert_equals(y.parentNode, null)
    assert_equals(y.firstChild, child)
    assert_equals(y.ownerDocument, document)
    assert_equals(child.ownerDocument, document)
    var doc = document.implementation.createDocument(null, null, null)
    assert_equals(doc.adoptNode(y), y)
    assert_equals(y.parentNode, null)
    assert_equals(y.firstChild, child)
    assert_equals(y.ownerDocument, doc)
    assert_equals(child.ownerDocument, doc)
}, "Adopting an Element called 'x<' should work.")

test(function() {
    var x = document.createElement(":good:times:")
    assert_equals(document.adoptNode(x), x);
    var doc = document.implementation.createDocument(null, null, null)
    assert_equals(doc.adoptNode(x), x)
    assert_equals(x.parentNode, null)
    assert_equals(x.ownerDocument, doc)
}, "Adopting an Element called ':good:times:' should work.")

test(function() {
    var doctype = document.doctype;
    assert_equals(doctype.parentNode, document)
    assert_equals(doctype.ownerDocument, document)
    assert_equals(document.adoptNode(doctype), doctype)
    assert_equals(doctype.parentNode, null)
    assert_equals(doctype.ownerDocument, document)
}, "Explicitly adopting a DocumentType should work.")

test(function() {
    var doc = document.implementation.createDocument(null, null, null)
    assert_throws_dom("NOT_SUPPORTED_ERR", function() { document.adoptNode(doc) })
}, "Adopting a Document should throw.")
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
