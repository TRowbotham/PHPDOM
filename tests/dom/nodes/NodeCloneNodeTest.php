<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\dom\HTMLElementInterfaces;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-cloneNode.html
 */
class NodeCloneNodeTest extends TestCase
{
    use DocumentGetter;
    use HTMLElementInterfaces;

    public function checkCopy($aOrig, $aCopy, $aType)
    {
        $this->assertNotSame($aOrig, $aCopy);
        $this->assertSame($aOrig->nodeType, $aCopy->nodeType, 'nodeType');
        $this->assertSame($aOrig->nodeName, $aCopy->nodeName, 'nodeName');

        if ($aOrig->nodeType === Node::ELEMENT_NODE) {
            $this->assertSame($aOrig->prefix, $aCopy->prefix, 'prefix');
            $this->assertSame(
                $aOrig->namespaceURI,
                $aCopy->namespaceURI,
                'namespaceURI'
            );
            $this->assertSame(
                $aOrig->localName,
                $aCopy->localName,
                'localName'
            );
            $this->assertSame($aOrig->tagName, $aCopy->tagName, 'tagName');
            $this->assertNotSame(
                $aOrig->attributes,
                $aCopy->attributes,
                'attributes'
            );
            $this->assertSame(
                $aOrig->attributes->length,
                $aCopy->attributes->length,
                'attributes->length'
            );

            for ($i = 0, $len = $aOrig->attributes->length; $i < $len; $i++) {
                $this->assertNotSame(
                    $aOrig->attributes[$i],
                    $aCopy->attributes[$i]
                );
                $this->assertSame(
                    $aOrig->attributes[$i]->name,
                    $aCopy->attributes[$i]->name,
                    'attribtues->name'
                );
                $this->assertSame(
                    $aOrig->attributes[$i]->localName,
                    $aCopy->attributes[$i]->localName,
                    'attribtues->localName'
                );
                $this->assertSame(
                    $aOrig->attributes[$i]->prefix,
                    $aCopy->attributes[$i]->prefix,
                    'attribtues->prefix'
                );
                $this->assertSame(
                    $aOrig->attributes[$i]->namespaceURI,
                    $aCopy->attributes[$i]->namespaceURI,
                    'attribtues->namespaceURI'
                );
                $this->assertSame(
                    $aOrig->attributes[$i]->value,
                    $aCopy->attributes[$i]->value,
                    'attribtues->value'
                );
            }
        }

        $this->assertInstanceOf($aType, $aOrig);
        $this->assertInstanceOf($aType, $aCopy);
    }

    /**
     * @dataProvider getHTMLElementInterfaces
     */
    public function testCloneElements($aLocalName, $aType)
    {
        $element = $this->getHTMLDocument()->createElement($aLocalName);
        $copy = $element->cloneNode();
        $this->checkCopy($element, $copy, $aType);
    }

    public function testCreateDocumentFragment()
    {
        $fragment = $this->getHTMLDocument()->createDocumentFragment();
        $copy = $fragment->cloneNode();
        $this->checkCopy($fragment, $copy, 'Rowbot\DOM\DocumentFragment');
    }

    public function testCreateTextNode()
    {
        $text = $this->getHTMLDocument()->createTextNode('hello world');
        $copy = $text->cloneNode();
        $this->checkCopy($text, $copy, 'Rowbot\DOM\Text');
        $this->assertSame($text->data, $copy->data, 'data');
        $this->assertSame($text->wholeText, $copy->wholeText, 'wholeText');
    }

    public function testCreateComment()
    {
        $comment = $this->getHTMLDocument()->createComment('a comment');
        $copy = $comment->cloneNode();
        $this->checkCopy($comment, $copy, 'Rowbot\DOM\Comment');
        $this->assertSame($comment->data, $copy->data, 'data');
    }

    public function testCreateElementWithAttributes()
    {
        $el = $this->getHTMLDocument()->createElement('foo');
        $el->setAttribute('a', 'b');
        $el->setAttribute('c', 'd');
        $copy = $el->cloneNode();
        $this->checkCopy($el, $copy, 'Rowbot\DOM\Element\Element');
    }

    public function testCreateElementNSHTML()
    {
        $el = $this->getHTMLDocument()->createElementNS(Namespaces::HTML, 'foo:div');
        $copy = $el->cloneNode();
        $this->checkCopy($el, $copy, 'Rowbot\DOM\Element\HTML\HTMLDivElement');
    }

    public function testCreateElementNSNonHTML()
    {
        $el = $this->getHTMLDocument()->createElementNS(
            "http://www.example.com/",
            'foo:div'
        );
        $copy = $el->cloneNode();
        $this->checkCopy($el, $copy, 'Rowbot\DOM\Element\Element');
    }

    public function testCreateProcessingInstruction()
    {
        $pi = $this->getHTMLDocument()->createProcessingInstruction('target', 'data');
        $copy = $pi->cloneNode();
        $this->checkCopy($pi, $copy, 'Rowbot\DOM\ProcessingInstruction');
        $this->assertSame($pi->data, $copy->data, 'data');
        $this->assertSame($pi->target, $copy->target, 'target');
    }

    public function testCreateDocumentType()
    {
        $doctype = $this->getHTMLDocument()->implementation->createDocumentType(
            'html',
            'public',
            'system'
        );
        $copy = $doctype->cloneNode();
        $this->checkCopy($doctype, $copy, 'Rowbot\DOM\DocumentType');
        $this->assertSame($doctype->name, $copy->name, 'name');
        $this->assertSame($doctype->publicId, $copy->publicId, 'publicId');
        $this->assertSame($doctype->systemId, $copy->systemId, 'systemId');
    }

    public function testCreateDocument()
    {
        $doc = $this->getHTMLDocument()->implementation->createDocument(null, null);
        $copy = $doc->cloneNode();
        $this->checkCopy($doc, $copy, 'Rowbot\DOM\Document');
        $this->assertSame('UTF-8', $copy->charset, 'charset');
        $this->assertSame($doc->charset, $copy->charset, 'charset');
        $this->assertSame($doc->contentType, 'application/xml', 'contentType');
        $this->assertSame($doc->contentType, $copy->contentType, 'contentType');
        $this->assertSame($doc->URL, 'about:blank', 'URL');
        $this->assertSame($doc->URL, $copy->URL, 'URL');
        $this->assertSame($doc->origin, 'null', 'origin');
        $this->assertSame($doc->origin, $copy->origin, 'origin');
        $this->assertSame($doc->compatMode, 'CSS1Compat', 'compatMode');
        $this->assertSame($doc->compatMode, $copy->compatMode, 'compatMode');
    }

    public function testCreateHTMLDocument()
    {
        $doc = $this->getHTMLDocument()->implementation->createHTMLDocument('title');
        $copy = $doc->cloneNode();
        $this->checkCopy($doc, $copy, 'Rowbot\DOM\HTMLDocument');
        $this->assertSame('', $copy->title, 'title');
    }

    public function testNodeWithChildren()
    {
        $doc = $this->getHTMLDocument();
        $parent = $doc->createElement('div');
        $child1 = $doc->createElement('div');
        $child2 = $doc->createElement('div');
        $gChild = $doc->createElement('div');

        $child2->appendChild($gChild);
        $parent->appendChild($child1);
        $parent->appendChild($child2);

        $copy = $parent->cloneNode(true);
        $this->checkCopy($parent, $copy, 'Rowbot\DOM\Element\HTML\HTMLDivElement');
        $this->assertSame(2, $copy->childNodes->length);

        $this->checkCopy(
            $child1,
            $copy->childNodes[0],
            'Rowbot\DOM\Element\HTML\HTMLDivElement'
        );
        $this->assertSame(0, $copy->childNodes[0]->childNodes->length);

        $this->checkCopy(
            $child2,
            $copy->childNodes[1],
            'Rowbot\DOM\Element\HTML\HTMLDivElement'
        );
        $this->assertSame(1, $copy->childNodes[1]->childNodes->length);
        $this->checkCopy(
            $gChild,
            $copy->childNodes[1]->childNodes[0],
            'Rowbot\DOM\Element\HTML\HTMLDivElement'
        );

        $copy = $parent->cloneNode(false);
        $this->checkCopy($parent, $copy, 'Rowbot\DOM\Element\HTML\HTMLDivElement');
        $this->assertSame(0, $copy->childNodes->length);
    }
}
