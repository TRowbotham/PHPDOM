<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-nodeName.html
 */
class NodeNodeNameTest extends TestCase
{
    use DocumentGetter;

    public function test()
    {
        $document = $this->getHTMLDocument();

        // For Element nodes, nodeName should return the same as tagName.
        $this->assertSame(
            'I',
            $document->createElementNS(Namespaces::HTML, 'I')->nodeName
        );
        $this->assertSame(
            'I',
            $document->createElementNS(Namespaces::HTML, 'i')->nodeName
        );
        $this->assertSame(
            'svg',
            $document->createElementNS(Namespaces::SVG, 'svg')->nodeName
        );
        $this->assertSame(
            'SVG',
            $document->createElementNS(Namespaces::SVG, 'SVG')->nodeName
        );
        $this->assertSame(
            'X:B',
            $document->createElementNS(Namespaces::HTML, 'x:b')->nodeName
        );

        // For Text nodes, nodeName should return "#text".
        $this->assertSame(
            '#text',
            $document->createTextNode('foo')->nodeName
        );

        // For ProcessingInstruction nodes, nodeName should return the target.
        $this->assertSame(
            'foo',
            $document->createProcessingInstruction('foo', 'bar')->nodeName
        );

        // For Comment nodes, nodeName should return "#comment".
        $this->assertSame(
            '#comment',
            $document->createComment('foo')->nodeName
        );

        // For Document nodes, nodeName should return "#document".
        $this->assertSame(
            '#document',
            $document->nodeName
        );

        // For DocumentType nodes, nodeName should return the name.
        $this->assertSame(
            'html',
            $document->doctype->nodeName
        );

        // For DocumentFragment nodes, nodeName should return "#document-fragment".
        $this->assertSame(
            '#document-fragment',
            $document->createDocumentFragment()->nodeName
        );
    }
}
