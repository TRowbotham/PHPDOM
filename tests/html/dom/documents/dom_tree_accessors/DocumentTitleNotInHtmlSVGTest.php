<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.title-not-in-html-svg.html
 */
class DocumentTitleNotInHtmlSVGTest extends AccessorTestCase
{
    public function testShouldNotBeAbleToSetDocumentTitleInXMLDocument(): void
    {
        $doc = $this->newXMLDocument();
        self::assertSame('', $doc->title);
        $doc->title = 'fail';
        self::assertSame('', $doc->title);
    }

    public function testShouldNotBeAbleToSetDocumentTitleInXMLDocumentWithHtmlTitleElement(): void
    {
        $document = new HTMLDocument();
        $doc = $this->newXMLDocument();
        $doc->documentElement->appendChild($document->createElementNS(Namespaces::HTML, 'html:title'));
        self::assertSame('', $doc->title);
        $doc->title = 'fail';
        self::assertSame('', $doc->title);
    }

    private function newXMLDocument()
    {
        return (new HTMLDocument())->implementation->createDocument(null, 'foo', null);
    }
}
