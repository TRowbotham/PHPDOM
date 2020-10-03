<?php
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
        $this->assertEquals($aOrig->nodeType, $aCopy->nodeType, 'nodeType');
        $this->assertEquals($aOrig->nodeName, $aCopy->nodeName, 'nodeName');

        if ($aOrig->nodeType === Node::ELEMENT_NODE) {
            $this->assertEquals($aOrig->prefix, $aCopy->prefix, 'prefix');
            $this->assertEquals(
                $aOrig->namespaceURI,
                $aCopy->namespaceURI,
                'namespaceURI'
            );
            $this->assertEquals(
                $aOrig->localName,
                $aCopy->localName,
                'localName'
            );
            $this->assertEquals($aOrig->tagName, $aCopy->tagName, 'tagName');
            $this->assertNotSame(
                $aOrig->attributes,
                $aCopy->attributes,
                'attributes'
            );
            $this->assertEquals(
                $aOrig->attributes->length,
                $aCopy->attributes->length,
                'attributes->length'
            );

            for ($i = 0, $len = $aOrig->attributes->length; $i < $len; $i++) {
                $this->assertNotEquals(
                    $aOrig->attributes[$i],
                    $aCopy->attributes[$i]
                );
                $this->assertEquals(
                    $aOrig->attributes[$i]->name,
                    $aCopy->attributes[$i]->name,
                    'attribtues->name'
                );
                $this->assertEquals(
                    $aOrig->attributes[$i]->localName,
                    $aCopy->attributes[$i]->localName,
                    'attribtues->localName'
                );
                $this->assertEquals(
                    $aOrig->attributes[$i]->prefix,
                    $aCopy->attributes[$i]->prefix,
                    'attribtues->prefix'
                );
                $this->assertEquals(
                    $aOrig->attributes[$i]->namespaceURI,
                    $aCopy->attributes[$i]->namespaceURI,
                    'attribtues->namespaceURI'
                );
                $this->assertEquals(
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
        $this->assertEquals($text->data, $copy->data, 'data');
        $this->assertEquals($text->wholeText, $copy->wholeText, 'wholeText');
    }

    public function testCreateComment()
    {
        $comment = $this->getHTMLDocument()->createComment('a comment');
        $copy = $comment->cloneNode();
        $this->checkCopy($comment, $copy, 'Rowbot\DOM\Comment');
        $this->assertEquals($comment->data, $copy->data, 'data');
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
        $this->assertEquals($pi->data, $copy->data, 'data');
        $this->assertEquals($pi->target, $copy->target, 'target');
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
        $this->assertEquals($doctype->name, $copy->name, 'name');
        $this->assertEquals($doctype->publicId, $copy->publicId, 'publicId');
        $this->assertEquals($doctype->systemId, $copy->systemId, 'systemId');
    }

    public function testCreateDocument()
    {
        $doc = $this->getHTMLDocument()->implementation->createDocument(null, null);
        $copy = $doc->cloneNode();
        $this->checkCopy($doc, $copy, 'Rowbot\DOM\Document');
        $this->assertEquals('UTF-8', $copy->charset, 'charset');
        $this->assertEquals($doc->charset, $copy->charset, 'charset');
        $this->assertEquals($doc->contentType, 'application/xml', 'contentType');
        $this->assertEquals($doc->contentType, $copy->contentType, 'contentType');
        $this->assertEquals($doc->URL, 'about:blank', 'URL');
        $this->assertEquals($doc->URL, $copy->URL, 'URL');
        $this->assertEquals($doc->origin, 'null', 'origin');
        $this->assertEquals($doc->origin, $copy->origin, 'origin');
        $this->assertEquals($doc->compatMode, 'CSS1', 'compatMode');
        $this->assertEquals($doc->compatMode, $copy->compatMode, 'compatMode');
    }

    public function testCreateHTMLDocument()
    {
        $doc = $this->getHTMLDocument()->implementation->createHTMLDocument('title');
        $copy = $doc->cloneNode();
        $this->checkCopy($doc, $copy, 'Rowbot\DOM\HTMLDocument');
        $this->assertEquals('', $copy->title, 'title');
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
        $this->assertEquals(2, $copy->childNodes->length);

        $this->checkCopy(
            $child1,
            $copy->childNodes[0],
            'Rowbot\DOM\Element\HTML\HTMLDivElement'
        );
        $this->assertEquals(0, $copy->childNodes[0]->childNodes->length);

        $this->checkCopy(
            $child2,
            $copy->childNodes[1],
            'Rowbot\DOM\Element\HTML\HTMLDivElement'
        );
        $this->assertEquals(1, $copy->childNodes[1]->childNodes->length);
        $this->checkCopy(
            $gChild,
            $copy->childNodes[1]->childNodes[0],
            'Rowbot\DOM\Element\HTML\HTMLDivElement'
        );

        $copy = $parent->cloneNode(false);
        $this->checkCopy($parent, $copy, 'Rowbot\DOM\Element\HTML\HTMLDivElement');
        $this->assertEquals(0, $copy->childNodes->length);
    }
}
