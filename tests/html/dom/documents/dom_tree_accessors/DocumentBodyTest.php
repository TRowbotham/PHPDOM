<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Element\HTML\HTMLBodyElement;
use Rowbot\DOM\Element\HTML\HTMLFrameSetElement;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\WindowTrait;
use TypeError;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/Document.body.html
 */
class DocumentBodyTest extends AccessorTestCase
{
    use WindowTrait;

    public function testChildlessDocument(): void
    {
        $doc = $this->createDocument();
        self::assertNull($doc->body);
    }

    public function testChildlessHtmlElement(): void
    {
        $doc = $this->createDocument();
        $doc->appendChild($doc->createElement('html'));
        self::assertNull($doc->body);
    }

    public function testBodyFollowedByFramesetInsideHTMLElement(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $b = $html->appendChild($doc->createElement('body'));
        $html->appendChild($doc->createElement('frameset'));
        self::assertSame($b, $doc->body);
    }

    public function testFramesetFollowedByBodyInsideHTMLElement(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $f = $html->appendChild($doc->createElement('frameset'));
        $html->appendChild($doc->createElement('body'));
        self::assertSame($f, $doc->body);
    }

    public function testBodyFollowedByFramesetInsideNonHTMLHtmlElement(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElementNS("http://example.org/test", 'html'));
        $html->appendChild($doc->createElement('body'));
        $html->appendChild($doc->createElement('frameset'));
        self::assertNull($doc->body);
    }

    public function testFramesetFollowedByBodyInsideNonHTMLHtmlElement(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElementNS("http://example.org/test", 'html'));
        $html->appendChild($doc->createElement('frameset'));
        $html->appendChild($doc->createElement('body'));
        self::assertNull($doc->body);
    }

    public function testNonHTMLBodyFollowedByBodyInsideTheHtmlElement(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $html->appendChild($doc->createElementNS("http://example.org/test", 'body'));
        $b = $html->appendChild($doc->createElement('body'));
        self::assertSame($b, $doc->body);
    }

    public function testNonHTMLFramesetFollowedByBodyInsideTheHtmlElement(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $html->appendChild($doc->createElementNS("http://example.org/test", 'frameset'));
        $b = $html->appendChild($doc->createElement('body'));
        self::assertSame($b, $doc->body);
    }

    public function testBodyInsideAnXElementFollowedByABody(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $x = $html->appendChild($doc->createElement('x'));
        $x->appendChild($doc->createElement('body'));
        $body = $html->appendChild($doc->createElement('body'));
        self::assertSame($body, $doc->body);
    }

    public function testFramesetInsideAnXElementFollowedByAFrameset(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $x = $html->appendChild($doc->createElement('x'));
        $x->appendChild($doc->createElement('frameset'));
        $frameset = $html->appendChild($doc->createElement('frameset'));
        self::assertSame($frameset, $doc->body);
    }

    // Root node is not a html element.
    public function testBodyAsTheRootNode(): void
    {
        $doc = $this->createDocument();
        $doc->appendChild($doc->createElement('body'));
        self::assertNull($doc->body);
    }

    public function testFramesetAsTheRootNode(): void
    {
        $doc = $this->createDocument();
        $doc->appendChild($doc->createElement('frameset'));
        self::assertNull($doc->body);
    }

    public function testBodyAsTheRootNodeWithABodyChild(): void
    {
        $doc = $this->createDocument();
        $body = $doc->appendChild($doc->createElement('body'));
        $body->appendChild($doc->createElement('frameset'));
        self::assertNull($doc->body);
    }

    public function testFramesetAsTheRootNodeWithABodyChild(): void
    {
        $doc = $this->createDocument();
        $frameset = $doc->appendChild($doc->createElement('frameset'));
        $frameset->appendChild($doc->createElement('body'));
        self::assertNull($doc->body);
    }

    public function testNonHTMLBodyAsTheRootNode(): void
    {
        $doc = $this->createDocument();
        $doc->appendChild($doc->createElementNS("http://example.org/test", "body"));
        self::assertNull($doc->body);
    }

    public function testNonHTMLFramesetAsTheRootNode(): void
    {
        $doc = $this->createDocument();
        $doc->appendChild($doc->createElementNS("http://example.org/test", "frameset"));
        self::assertNull($doc->body);
    }

    public function testExistingDocumentsBody(): void
    {
        $document = self::getWindow()->document;
        self::assertNotNull($document->body);
        self::assertInstanceOf(HTMLBodyElement::class, $document->body);
        self::assertSame('BODY', $document->body->tagName);
    }

    public function testSettingDocumentBodyToAString(): HTMLBodyElement
    {
        $document = self::getWindow()->document;
        $originalBody = $document->body;
        $this->assertThrows(static function () use ($document): void {
            $document->body = 'text';
        }, TypeError::class);
        self::assertSame($originalBody, $document->body);

        return $originalBody;
    }

    /**
     * @depends testSettingDocumentBodyToAString
     */
    public function testSettingDocumentBodyToADivElement(HTMLBodyElement $originalBody): void
    {
        $document = self::getWindow()->document;
        $this->assertThrows(static function () use ($document): void {
            $document->body = $document->createElement('div');
        }, HierarchyRequestError::class);
        self::assertSame($originalBody, $document->body);
    }

    public function testSettingDocumentBodyWhenTheresNoRootElement(): void
    {
        $doc = $this->createDocument();
        $this->assertThrows(static function () use ($doc): void {
            $doc->body = $doc->createElement('body');
        }, HierarchyRequestError::class);
        self::assertNull($doc->body);
    }

    public function testSettingDocumentBodyToANewBodyElement(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument();
        $newBody = $doc->createElement('body');
        self::assertInstanceOf(HTMLBodyElement::class, $newBody);
        self::assertSame('BODY', $newBody->tagName);

        $doc->body = $newBody;
        self::assertSame($newBody, $doc->body);
    }

    public function testSettingDocumentBodyToANewFramesetElement(): void
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument();
        $newFrameset = $doc->createElement('frameset');
        self::assertInstanceOf(HTMLFrameSetElement::class, $newFrameset);
        self::assertSame('FRAMESET', $newFrameset->tagName);

        $doc->body = $newFrameset;
        self::assertSame($newFrameset, $doc->body);
    }

    public function testSettingDocumentBodyToABodyWillReplaceAnExistingFramesetIfThereIsOne(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $f = $html->appendChild($doc->createElement('frameset'));
        self::assertSame($f, $doc->body);

        $b = $doc->createElement('body');
        $doc->body = $b;
        self::assertNull($f->parentNode);
        self::assertSame($b, $doc->body);
    }

    public function testSettingDocumentBodyToAFramesetWillReplaceAnExistingBodyIfThereIsOne(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $b = $html->appendChild($doc->createElement('body'));
        self::assertSame($b, $doc->body);

        $f = $doc->createElement('frameset');
        $doc->body = $f;

        self::assertNull($b->parentNode);
        self::assertSame($f, $doc->body);
    }

    public function testSettingDocumentBodyToAFramesetWillReplaceTheFirstExistingBodyFrameset(): void
    {
        $doc = $this->createDocument();
        $html = $doc->appendChild($doc->createElement('html'));
        $b = $html->appendChild($doc->createElement('body'));
        $f1 = $html->appendChild($doc->createElement('frameset'));
        self::assertSame($b, $doc->body);

        $f2 = $doc->createElement('frameset');
        $doc->body = $f2;

        self::assertNull($b->parentNode);
        self::assertSame($html, $f1->parentNode);
        self::assertSame($f2, $doc->body);
        self::assertSame($f1, $f2->nextSibling);
    }

    public function testSettingDocumentBodyToANewBodyElementWhenTheRootElementIsATestElement(): void
    {
        $doc = $this->createDocument();
        $doc->appendChild($doc->createElement('test'));
        $newBody = $doc->createElement('body');
        $doc->body = $newBody;
        self::assertSame($newBody, $doc->documentElement->firstChild);
        self::assertNull($doc->body);
    }

    private function createDocument(): HTMLDocument
    {
        $doc = self::getWindow()->document->implementation->createHTMLDocument('');
        $doc->removeChild($doc->documentElement);

        return $doc;
    }

    public static function getDocumentName(): string
    {
        return 'document.body.html';
    }
}
