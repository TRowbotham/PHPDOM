<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Document;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTML\HTMLAnchorElement;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\XMLDocument;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-constructor.html
 */
class DocumentConstructorTest extends NodeTestCase
{
    use WindowTrait;

    public function testInterfaces(): void
    {
        $doc = new Document();
        $this->assertInstanceOf(Node::class, $doc, 'message');
        $this->assertInstanceOf(Document::class, $doc);
        $this->assertNotInstanceOf(XMLDocument::class, $doc);
    }

    public function testChildren(): void
    {
        $doc = new Document();
        $this->assertNull($doc->firstChild);
        $this->assertNull($doc->lastChild);
        $this->assertNull($doc->doctype);
        $this->assertNull($doc->documentElement);
        $this->assertSame([], iterator_to_array($doc->childNodes));
    }

    public function testMetadata(): void
    {
        $doc = new Document();
        $this->assertNull($doc->location);
        $this->assertSame('about:blank', $doc->URL);
        $this->assertSame('about:blank', $doc->documentURI);
        $this->assertSame('CSS1Compat', $doc->compatMode);
        $this->assertSame('UTF-8', $doc->characterSet);
        $this->assertSame('application/xml', $doc->contentType);
        $this->assertSame('DIV', $doc->createElement('DIV')->localName);
        $this->assertInstanceOf(Element::class, $doc->createElement('a'));
    }

    public function testCharsetAliases(): void
    {
        $doc = new Document();
        $this->assertSame('UTF-8', $doc->characterSet);
        $this->assertSame('UTF-8', $doc->charset);
        $this->assertSame('UTF-8', $doc->inputEncoding);
    }

    public function testURLParsing(): void
    {
        $doc = new Document();
        $a = $doc->createElementNS("http://www.w3.org/1999/xhtml", 'a');
        $this->assertInstanceOf(HTMLAnchorElement::class, $a);
        $a->href = "http://example.org/?\u{00E4}";
        $this->assertSame("http://example.org/?%C3%A4", $a->href);
    }

    public static function getDocumentName(): string
    {
        return 'Document-constructor.html';
    }
}
