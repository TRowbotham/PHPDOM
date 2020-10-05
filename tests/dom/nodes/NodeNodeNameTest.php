<?php
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
        $this->assertEquals(
            'I',
            $document->createElementNS(Namespaces::HTML, 'I')->nodeName
        );
        $this->assertEquals(
            'I',
            $document->createElementNS(Namespaces::HTML, 'i')->nodeName
        );
        $this->assertEquals(
            'svg',
            $document->createElementNS(Namespaces::SVG, 'svg')->nodeName
        );
        $this->assertEquals(
            'SVG',
            $document->createElementNS(Namespaces::SVG, 'SVG')->nodeName
        );
        $this->assertEquals(
            'X:B',
            $document->createElementNS(Namespaces::HTML, 'x:b')->nodeName
        );

        // For Text nodes, nodeName should return "#text".
        $this->assertEquals(
            '#text',
            $document->createTextNode('foo')->nodeName
        );

        // For ProcessingInstruction nodes, nodeName should return the target.
        $this->assertEquals(
            'foo',
            $document->createProcessingInstruction('foo', 'bar')->nodeName
        );

        // For Comment nodes, nodeName should return "#comment".
        $this->assertEquals(
            '#comment',
            $document->createComment('foo')->nodeName
        );

        // For Document nodes, nodeName should return "#document".
        $this->assertEquals(
            '#document',
            $document->nodeName
        );

        // For DocumentType nodes, nodeName should return the name.
        $this->assertEquals(
            'html',
            $document->doctype->nodeName
        );

        // For DocumentFragment nodes, nodeName should return "#document-fragment".
        $this->assertEquals(
            '#document-fragment',
            $document->createDocumentFragment()->nodeName
        );
    }
}
