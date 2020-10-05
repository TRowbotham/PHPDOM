<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-isSameNode.html
 */
class NodeIsSameNodeTest extends TestCase
{
    use DocumentGetter;

    /**
     * Doctypes should be compared on reference.
     */
    public function testDoctypes()
    {
        $document = $this->getHTMLDocument();
        $doctype1 = $document->implementation->createDocumentType(
            'qualifiedName',
            'publicId',
            'systemId'
        );
        $doctype2 = $document->implementation->createDocumentType(
            'qualifiedName',
            'publicId',
            'systemId'
        );

        $this->assertTrue($doctype1->isSameNode($doctype1));
        $this->assertFalse($doctype1->isSameNode($doctype2));
        $this->assertFalse($doctype1->isSameNode(null));
    }

    /**
     * Elements hsould be compared on reference (namespaced element).
     */
    public function testElements1()
    {
        $document = $this->getHTMLDocument();
        $element1 = $document->createElementNS('namespace', 'prefix:localName');
        $element2 = $document->createElementNS('namespace', 'prefix:localName');

        $this->assertTrue($element1->isSameNode($element1));
        $this->assertFalse($element1->isSameNode($element2));
        $this->assertFalse($element1->isSameNode(null));
    }

    /**
     * Elements should be compared on reference (namespaced attribute).
     */
    public function testElements2()
    {
        $document = $this->getHTMLDocument();
        $element1 = $document->createElement("element");
        $element1->setAttributeNS("namespace", "prefix:localName", "value");
        $element2 = $document->createElement("element");
        $element2->setAttributeNS("namespace", "prefix:localName", "value");

        $this->assertTrue($element1->isSameNode($element1));
        $this->assertFalse($element1->isSameNode($element2));
        $this->assertFalse($element1->isSameNode(null));
    }

    /**
     * Processing instructions should be compared on reference.
     */
    public function testProcessingInstruction()
    {
        $document = $this->getHTMLDocument();
        $pi1 = $document->createProcessingInstruction("target", 'data');
        $pi2 = $document->createProcessingInstruction("target", 'data');

        $this->assertTrue($pi1->isSameNode($pi1));
        $this->assertFalse($pi1->isSameNode($pi2));
        $this->assertFalse($pi1->isSameNode(null));
    }

    /**
     * Text nodes should be compared on reference.
     */
    public function testTextNode()
    {
        $document = $this->getHTMLDocument();
        $text1 = $document->createTextNode('data');
        $text2 = $document->createTextNode('data');

        $this->assertTrue($text1->isSameNode($text1));
        $this->assertFalse($text1->isSameNode($text2));
        $this->assertFalse($text1->isSameNode(null));
    }

    /**
     * Comments should be compared on reference.
     */
    public function testCommentNode()
    {
        $document = $this->getHTMLDocument();
        $comment1 = $document->createComment('data');
        $comment2 = $document->createComment('data');

        $this->assertTrue($comment1->isSameNode($comment1));
        $this->assertFalse($comment1->isSameNode($comment2));
        $this->assertFalse($comment1->isSameNode(null));
    }

    /**
     * Document fragments should be compared on reference.
     */
    public function testDocumentFragment()
    {
        $document = $this->getHTMLDocument();
        $documentFragment1 = $document->createDocumentFragment();
        $documentFragment2 = $document->createDocumentFragment();

        $this->assertTrue($documentFragment1->isSameNode($documentFragment1));
        $this->assertFalse($documentFragment1->isSameNode($documentFragment2));
        $this->assertFalse($documentFragment1->isSameNode(null));
    }

    /**
     * Documents should not be compared on reference.
     */
    public function testDocument()
    {
        $document = $this->getHTMLDocument();
        $document1 = $document->implementation->createDocument('', '');
        $document2 = $document->implementation->createDocument('', '');

        $this->assertTrue($document1->isSameNode($document1));
        $this->assertFalse($document1->isSameNode($document2));
        $this->assertFalse($document1->isSameNode(null));
    }
}
