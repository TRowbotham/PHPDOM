<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\Document;
use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLHeadElement;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLTitleElement;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/DOMImplementation-createHTMLDocument.html
 */
class DOMImplementationCreateHTMLDocumentTest extends TestCase
{
    private static $document;

    /**
     * @dataProvider createDocumentArgsProvider
     */
    public function testCreateHTMLDocument(HTMLDocument $doc, ?string $expectedTitle, string $normalizedTitle): void
    {
        $this->assertInstanceOf(Document::class, $doc);
        $this->assertInstanceOf(Node::class, $doc);
        $this->assertSame(2, $doc->childNodes->length);

        $doctype = $doc->doctype;

        $this->assertInstanceOf(DocumentType::class, $doctype);
        $this->assertInstanceOf(Node::class, $doctype);
        $this->assertSame('html', $doctype->name);
        $this->assertSame('', $doctype->publicId);
        $this->assertSame('', $doctype->systemId);

        $documentElement = $doc->documentElement;

        $this->assertInstanceOf(HTMLHtmlElement::class, $documentElement);
        $this->assertSame(2, $documentElement->childNodes->length);
        $this->assertSame('html', $documentElement->localName);
        $this->assertSame('HTML', $documentElement->tagName);

        $head = $documentElement->firstChild;

        $this->assertInstanceOf(HTMLHeadElement::class, $head);
        $this->assertSame('head', $head->localName);
        $this->assertSame('HEAD', $head->tagName);

        if ($expectedTitle !== null) {
            $this->assertSame(1, $head->childNodes->length);

            $title = $head->firstChild;

            $this->assertInstanceOf(HTMLTitleElement::class, $title);
            $this->assertSame('title', $title->localName);
            $this->assertSame('TITLE', $title->tagName);
            $this->assertSame(1, $title->childNodes->length);
            $this->assertSame($expectedTitle, $title->firstChild->data);
        } else {
            $this->assertSame(0, $head->childNodes->length);
        }

        $body = $documentElement->lastChild;

        $this->assertInstanceOf(HTMLBodyElement::class, $body);
        $this->assertSame('body', $body->localName);
        $this->assertSame('BODY', $body->tagName);
        $this->assertSame(0, $body->childNodes->length);
    }

    public function testCreateHTMLDocumentMetadata(): void
    {
        $doc = self::loadDocument()->implementation->createHTMLDocument('test');

        $this->assertSame('about:blank', $doc->URL);
        $this->assertSame('about:blank', $doc->documentURI);
        // $this->assertSame('CSS1Compat', $doc->compatMode);
        $this->assertSame('UTF-8', $doc->characterSet);
        $this->assertSame('text/html', $doc->contentType);
        $this->assertSame('div', $doc->createElement('DIV')->localName);
    }

    public function testCreateHTMLDocumentCharacterSetAliases(): void
    {
        $this->markTestSkipped('We don\'t support characterSet aliases.');

        $doc = self::loadDocument()->implementation->createHTMLDocument('test');

        $this->assertSame('UTF-8', $doc->characterSet);
        $this->assertSame('UTF-8', $doc->charset);
        $this->assertSame('UTF-8', $doc->inputEncoding);
    }

    public function testURLParsing(): void
    {
        $doc = self::loadDocument()->implementation->createHTMLDocument('test');
        $a = $doc->createElement('a');
        $a->href = "http://example.org/?\u{00E4}";

        $this->assertSame('http://example.org/?%C3%A4', $a->href);
    }

    public function testLocationGetterIsNullOutsideOfBrowserContext(): void
    {
        $doc = self::loadDocument()->implementation->createHTMLDocument();

        $this->assertNull($doc->location);
    }

    public function createDocumentArgsProvider(): Generator
    {
        self::loadDocument();

        $tests = [
            ["", "", ""],
            // [null, "null", "null"],
            // [undefined, undefined, ""],
            ["foo  bar baz", "foo  bar baz", "foo bar baz"],
            ["foo\t\tbar baz", "foo\t\tbar baz", "foo bar baz"],
            ["foo\n\nbar baz", "foo\n\nbar baz", "foo bar baz"],
            ["foo\f\fbar baz", "foo\f\fbar baz", "foo bar baz"],
            ["foo\r\rbar baz", "foo\r\rbar baz", "foo bar baz"],
        ];

        foreach ($tests as [$title, $expectedTitle, $normalizedTitle]) {
            $doc = self::$document->implementation->createHTMLDocument($title);

            yield [$doc, $expectedTitle, $normalizedTitle];
        }
    }

    public static function loadDocument(): HTMLDocument
    {
        if (!self::$document) {
            self::$document = new HTMLDocument();
        }

        return self::$document;
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
