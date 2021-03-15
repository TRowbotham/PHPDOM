<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Node-cloneNode-document-with-doctype.html
 */
class Node_cloneNode_document_with_doctypeTest extends NodeTestCase
{
    public function testCreatedWithTheCreateDocumentCreateDocumentType(): void
    {
        $document = new HTMLDocument();
        $doctype = $document->implementation->createDocumentType('name', 'publicId', 'systemId');
        $doc = $document->implementation->createDocument('namespace', '', $doctype);
        $clone = $doc->cloneNode(true);

        self::assertSame(1, $clone->childNodes->length);
        self::assertSame(Node::DOCUMENT_TYPE_NODE, $clone->childNodes[0]->nodeType);
        self::assertSame('name', $clone->childNodes[0]->name);
        self::assertSame('publicId', $clone->childNodes[0]->publicId);
        self::assertSame('systemId', $clone->childNodes[0]->systemId);
    }

    public function testCreatedWithTheCreateHTMLDocument(): void
    {
        $document = new HTMLDocument();
        $doc = $document->implementation->createHTMLDocument();
        $clone = $doc->cloneNode(true);

        self::assertSame(2, $clone->childNodes->length);
        self::assertSame(Node::DOCUMENT_TYPE_NODE, $clone->childNodes[0]->nodeType);
        self::assertSame('html', $clone->childNodes[0]->name);
        self::assertSame('', $clone->childNodes[0]->publicId);
        self::assertSame('', $clone->childNodes[0]->systemId);
    }

    public function testCreatedWithDOMParser(): void
    {
        $parser = new DOMParser();
        $doc = $parser->parseFromString("<!DOCTYPE html><html></html>", "text/html");
        $clone = $doc->cloneNode(true);

        self::assertSame(2, $clone->childNodes->length);
        self::assertSame(Node::DOCUMENT_TYPE_NODE, $clone->childNodes[0]->nodeType);
        self::assertSame('html', $clone->childNodes[0]->name);
        self::assertSame('', $clone->childNodes[0]->publicId);
        self::assertSame('', $clone->childNodes[0]->systemId);
    }
}
