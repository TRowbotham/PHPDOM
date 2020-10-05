<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-isEqualNode.html
 */
class NodeIsEqualNodeTest extends TestCase
{
    use DocumentGetter;

    /**
     * Doctypes should be compared on name, public ID, and system ID.
     */
    public function testDoctype()
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
        $doctype3 = $document->implementation->createDocumentType(
            'qualifiedName2',
            'publicId',
            'systemId'
        );
        $doctype4 = $document->implementation->createDocumentType(
            'qualifiedName',
            'publicId2',
            'systemId'
        );
        $doctype5 = $document->implementation->createDocumentType(
            'qualifiedName',
            'publicId',
            'systemId3'
        );

        $this->assertTrue($doctype1->isEqualNode($doctype1));
        $this->assertTrue($doctype1->isEqualNode($doctype2));
        $this->assertFalse($doctype1->isEqualNode($doctype3));
        $this->assertFalse($doctype1->isEqualNode($doctype4));
        $this->assertFalse($doctype1->isEqualNode($doctype5));
    }

    /**
     * Elements should be compared on namespace, namespace prefix, local name,
     * and number of attributes.
     */
    public function testElement1()
    {
        $document = $this->getHTMLDocument();
        $element1 = $document->createElementNS('namespace', 'prefix:localName');
        $element2 = $document->createElementNS('namespace', 'prefix:localName');
        $element3 = $document->createElementNS('namespace2', 'prefix:localName');
        $element4 = $document->createElementNS('namespace', 'prefix2:localName');
        $element5 = $document->createElementNS('namespace', 'prefix:localName2');

        $element6 = $document->createElementNS('namespace', 'prefix:localName');
        $element6->setAttribute('foo', 'bar');

        $this->assertTrue($element1->isEqualNode($element1));
        $this->assertTrue($element1->isEqualNode($element2));
        $this->assertFalse($element1->isEqualNode($element3));
        $this->assertFalse($element1->isEqualNode($element4));
        $this->assertFalse($element1->isEqualNode($element5));
        $this->assertFalse($element1->isEqualNode($element6));
    }

    /**
     * Elements should be compared on attribute namespace, local name, and
     * value.
     */
    public function testElement2()
    {
        $document = $this->getHTMLDocument();
        $element1 = $document->createElement('element');
        $element1->setAttributeNS('namespace', 'prefix:localName', 'value');

        $element2 = $document->createElement('element');
        $element2->setAttributeNS('namespace', 'prefix:localName', 'value');

        $element3 = $document->createElement('element');
        $element3->setAttributeNS('namespace2', 'prefix:localName', 'value');

        $element4 = $document->createElement('element');
        $element4->setAttributeNS('namespace', 'prefix2:localName', 'value');

        $element5 = $document->createElement('element');
        $element5->setAttributeNS('namespace', 'prefix:localName2', 'value');

        $element6 = $document->createElement('element');
        $element6->setAttributeNS('namespace', 'prefix:localName', 'value2');

        $this->assertTrue($element1->isEqualNode($element1));
        $this->assertTrue($element1->isEqualNode($element2));
        $this->assertFalse($element1->isEqualNode($element3));
        $this->assertTrue($element1->isEqualNode($element4));
        $this->assertFalse($element1->isEqualNode($element5));
        $this->assertFalse($element1->isEqualNode($element6));
    }

    /**
     * Processing instructions should be compared on target and data.
     */
    public function testProcessingInstruction()
    {
        $document = $this->getHTMLDocument();
        $pi1 = $document->createProcessingInstruction('target', 'data');
        $pi2 = $document->createProcessingInstruction('target', 'data');
        $pi3 = $document->createProcessingInstruction('target2', 'data');
        $pi4 = $document->createProcessingInstruction('target', 'data2');

        $this->assertTrue($pi1->isEqualNode($pi1));
        $this->assertTrue($pi1->isEqualNode($pi2));
        $this->assertFalse($pi1->isEqualNode($pi3));
        $this->assertFalse($pi1->isEqualNode($pi4));
    }

    /**
     * Text nodes should be compared on data.
     */
    public function testText()
    {
        $document = $this->getHTMLDocument();
        $text1 = $document->createTextNode('data');
        $text2 = $document->createTextNode('data');
        $text3 = $document->createTextNode('data2');

        $this->assertTrue($text1->isEqualNode($text1));
        $this->assertTrue($text1->isEqualNode($text2));
        $this->assertFalse($text1->isEqualNode($text3));
    }

    /**
     * Comments should be compared on data.
     */
    public function testComment()
    {
        $document = $this->getHTMLDocument();
        $comment1 = $document->createComment('data');
        $comment2 = $document->createComment('data');
        $comment3 = $document->createComment('data2');

        $this->assertTrue($comment1->isEqualNode($comment1));
        $this->assertTrue($comment1->isEqualNode($comment2));
        $this->assertFalse($comment1->isEqualNode($comment3));
    }

    /**
     * Document fragments should not be compared based on properties.
     */
    public function testDocumentFragment()
    {
        $document = $this->getHTMLDocument();
        $documentFragment1 = $document->createDocumentFragment();
        $documentFragment2 = $document->createDocumentFragment();

        $this->assertTrue($documentFragment1->isEqualNode($documentFragment1));
        $this->assertTrue($documentFragment1->isEqualNode($documentFragment2));
    }

    /**
     * Documents should not be compared based on properties.
     */
    public function testDocument()
    {
        $document = $this->getHTMLDocument();
        $document1 = $document->implementation->createDocument('', '');
        $document2 = $document->implementation->createDocument('', '');

        $this->assertTrue($document1->isEqualNode($document1));
        $this->assertTrue($document1->isEqualNode($document2));

        $htmlDoctype = $document->implementation->createDocumentType(
            'html',
            '',
            ''
        );
        $document3 = $document->implementation->createDocument(
            'http://www.w3.org/1999/xhtml',
            'html',
            $htmlDoctype
        );
        $document3->documentElement->appendChild(
            $document3->createElement('head')
        );
        $document3->documentElement->appendChild(
            $document3->createElement('body')
        );
        $document4 = $document->implementation->createHTMLDocument();
        $this->assertTrue($document3->isEqualNode($document4));
    }

    public function getTestData()
    {
        $document = $this->getHTMLDocument();

        return [
            [function () use ($document) {
                return $document->createElement('foo');
            }],
            [function () use ($document) {
                return $document->createDocumentFragment();
            }],
            [function () use ($document) {
                return $document->implementation->createDocument('', '');
            }],
            [function () use ($document) {
                return $document->implementation->createHTMLDocument();
            }]
        ];
    }

    /**
     * Node equality testing should test descendant equality too.
     *
     * @dataProvider getTestData
     */
    public function testDeepEquality($parentFactory)
    {
        $document = $this->getHTMLDocument();
        $parentA = $parentFactory();
        $parentB = $parentFactory();

        $parentA->appendChild($document->createComment('data'));
        $this->assertFalse($parentA->isEqualNode($parentB));
        $parentB->appendChild($document->createComment('data'));
        $this->assertTrue($parentA->isEqualNode($parentB));
    }
}
