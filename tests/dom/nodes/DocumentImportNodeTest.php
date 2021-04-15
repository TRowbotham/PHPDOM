<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-importNode.html
 */
class DocumentImportNodeTest extends TestCase
{
    use DocumentGetter;

    public function testNoDeepArgument()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('Title');
        $div = $doc->body->appendChild($doc->createElement('div'));
        $div->appendChild($doc->createElement('span'));
        $this->assertSame($doc, $div->ownerDocument);
        $this->assertSame($doc, $div->firstChild->ownerDocument);
        $newDiv = $document->importNode($div);
        $this->assertSame($doc, $div->ownerDocument);
        $this->assertSame($doc, $div->firstChild->ownerDocument);
        $this->assertSame($document, $newDiv->ownerDocument);
        $this->assertNull($newDiv->firstChild);
    }

    public function testTrueDeepArgument()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('Title');
        $div = $doc->body->appendChild($doc->createElement('div'));
        $div->appendChild($doc->createElement('span'));
        $this->assertSame($doc, $div->ownerDocument);
        $this->assertSame($doc, $div->firstChild->ownerDocument);
        $newDiv = $document->importNode($div, true);
        $this->assertSame($doc, $div->ownerDocument);
        $this->assertSame($doc, $div->firstChild->ownerDocument);
        $this->assertSame($document, $newDiv->ownerDocument);
        $this->assertSame($document, $newDiv->firstChild->ownerDocument);
    }

    public function testFalseDeepArgument()
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('Title');
        $div = $doc->body->appendChild($doc->createElement('div'));
        $div->appendChild($doc->createElement('span'));
        $this->assertSame($doc, $div->ownerDocument);
        $this->assertSame($doc, $div->firstChild->ownerDocument);
        $newDiv = $document->importNode($div, false);
        $this->assertSame($doc, $div->ownerDocument);
        $this->assertSame($doc, $div->firstChild->ownerDocument);
        $this->assertSame($document, $newDiv->ownerDocument);
        $this->assertNull($newDiv->firstChild);
    }

    public function testImportAttrWithNamespaceAndPrefixCorrectly(): void
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('Title');
        $doc->body->setAttributeNS("http://example.com/", "p:name", "value");
        $originalAttr = $doc->body->getAttributeNodeNS("http://example.com/", "name");
        $imported = $document->importNode($originalAttr, true);

        $this->assertSame($originalAttr->prefix, $imported->prefix);
        $this->assertSame($originalAttr->namespaceURI, $imported->namespaceURI);
        $this->assertSame($originalAttr->localName, $imported->localName);
    }
}
