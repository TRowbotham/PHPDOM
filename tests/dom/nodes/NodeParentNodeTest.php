<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-parentNode.html
 */
class NodeParentNodeTest extends TestCase
{
    use DocumentGetter;

    /**
     * Document
     */
    public function test1()
    {
        $this->assertNull($this->getHTMLDocument()->parentNode);
    }

    /**
     * Doctype
     */
    public function test2()
    {
        $document = $this->getHTMLDocument();
        $this->assertSame($document, $document->doctype->parentNode);
    }

    /**
     * Root element
     */
    public function test3()
    {
        $document = $this->getHTMLDocument();
        $this->assertSame($document, $document->documentElement->parentNode);
    }

    /**
     * Element
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $this->assertNull($el->parentNode);
        $document->body->appendChild($el);
        $this->assertSame($document->body, $el->parentNode);
    }

    public function testIframe()
    {
        $this->markTestIncomplete('We don\'t yet support iframes.');
        $doc = $iframe->contentDocument;
        $iframe->parentNode->removeChild($iframe);
        $this->assertSame($doc, $doc->firstChild->parentNode);
    }
}
