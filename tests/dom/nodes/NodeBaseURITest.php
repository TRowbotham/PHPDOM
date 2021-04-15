<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-baseURI.html
 */
class NodeBaseURITest extends TestCase
{
    use DocumentGetter;

    // "For elements belonging to document, baseURI should be document url"
    public function test1()
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElement('div');
        $document->body->appendChild($element);
        $this->assertEquals($document->URL, $element->baseURI);
    }

    // "For elements unassigned to document, baseURI should be document url"
    public function test2()
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElement('div');
        $this->assertEquals($document->URL, $element->baseURI);
    }

    // "For elements belonging to document fragments, baseURI should be document
    // url"
    public function test3()
    {
        $document = $this->getHTMLDocument();
        $fragment = $document->createDocumentFragment();
        $element = $document->createElement('div');
        $fragment->appendChild($element);
        $this->assertEquals($document->URL, $element->baseURI);
    }

    // "After inserting fragment into document, element baseURI should be
    // document url"
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $fragment = $document->createDocumentFragment();
        $element = $document->createElement('div');
        $fragment->appendChild($element);
        $document->body->appendChild($fragment);
        $this->assertEquals($document->URL, $element->baseURI);
    }
}
