<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTML\HTMLElement;
use Rowbot\DOM\Element\HTML\HTMLSpanElement;
use Rowbot\DOM\Element\HTML\HTMLUnknownElement;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

use function array_merge;
use function explode;
use function mb_strpos;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createElementNS.html
 */
class DocumentCreateElementNSTest extends TestCase
{
    use DocumentGetter;
    use CreateElementNSTests;

    public function getTestData(): array
    {
        return array_merge($this->getCreateElementNSTests(), [
            /* Arrays with three elements:
             *   the namespace argument
             *   the qualifiedName argument
             *   the expected exception, or null if none
             */
            ["", "", InvalidCharacterError::class],
            [null, "", InvalidCharacterError::class],
            // [undefined, "", InvalidCharacterError::class],
            // ["http://example.com/", null, null],
            ["http://example.com/", "", InvalidCharacterError::class],
            // ["/", null, null],
            ["/", "", InvalidCharacterError::class],
            // ["http://www.w3.org/XML/1998/namespace", null, null],
            ["http://www.w3.org/XML/1998/namespace", "", InvalidCharacterError::class],
            // ["http://www.w3.org/2000/xmlns/", null, NamespaceError::class],
            ["http://www.w3.org/2000/xmlns/", "", InvalidCharacterError::class],
            // ["foo:", null, null],
            ["foo:", "", InvalidCharacterError::class],
        ]);
    }

    /**
     * @dataProvider getTestData
     */
    public function test1($namespace, $qualifiedName, $expected): void
    {
        $document = $this->getHTMLDocument();

        foreach (['HTML document', 'XML document', 'XHTML document'] as $desc) {
            if ($desc === 'HTML document') {
                $doc = $document;
            } elseif ($desc === 'XML document') {
                $doc = $document->implementation->createDocument(null, null, null);
            } elseif ($desc === 'XHTML document') {
                $doc = $document->implementation->createDocument(
                    Namespaces::HTML,
                    '',
                    $document->implementation->createDocumentType('html', '', '')
                );
            }

            if ($expected !== null) {
                $this->expectException($expected);
                $doc->createElementNS($namespace, $qualifiedName);

                return;
            }

            $element = $doc->createElementNS($namespace, $qualifiedName);
            $this->assertNotNull($element);
            $this->assertEquals(Node::ELEMENT_NODE, $element->nodeType);
            $this->assertEquals($element::ELEMENT_NODE, $element->nodeType);
            $this->assertNull($element->nodeValue);
            $this->assertSame($doc, $element->ownerDocument);

            $qualified = (string) $qualifiedName;
            $names = [null, $qualified];

            if (mb_strpos($qualified, ':') !== false) {
                $names = explode(':', $qualified, 2);
            }

            $this->assertEquals($names[0], $element->prefix);
            $this->assertEquals($names[1], $element->localName);
            $this->assertEquals($qualified, $element->tagName);
            $this->assertEquals($qualified, $element->nodeName);
            $this->assertEquals(
                ($namespace === '' ? null : $namespace),
                $element->namespaceURI
            );
        }
    }

    /**
     * Lower-case HTML element without a prefix.
     */
    public function test2(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS(Namespaces::HTML, 'span');
        $this->assertEquals(Namespaces::HTML, $element->namespaceURI);
        $this->assertNull($element->prefix);
        $this->assertEquals('span', $element->localName);
        $this->assertEquals('SPAN', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertInstanceOf(HTMLElement::class, $element);
        $this->assertInstanceOf(HTMLSpanElement::class, $element);
    }

    /**
     * Lower-case HTML element without a prefix.
     */
    public function test3(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS(Namespaces::HTML, 'html:span');
        $this->assertEquals(Namespaces::HTML, $element->namespaceURI);
        $this->assertEquals('html', $element->prefix);
        $this->assertEquals('span', $element->localName);
        $this->assertEquals('HTML:SPAN', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertInstanceOf(HTMLElement::class, $element);
        $this->assertInstanceOf(HTMLSpanElement::class, $element);
    }

    /**
     * Lower-case non-HTML element without a prefix.
     */
    public function test4(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS('test', 'span');
        $this->assertEquals('test', $element->namespaceURI);
        $this->assertNull($element->prefix);
        $this->assertEquals('span', $element->localName);
        $this->assertEquals('span', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertNotInstanceOf(HTMLElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }

    /**
     * Lower-case non-HTML element with a prefix.
     */
    public function test5(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS('test', 'html:span');
        $this->assertEquals('test', $element->namespaceURI);
        $this->assertEquals('html', $element->prefix);
        $this->assertEquals('span', $element->localName);
        $this->assertEquals('html:span', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertNotInstanceOf(HTMLElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }

    /**
     * Upper-case HTML element without a prefix.
     */
    public function test6(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS(Namespaces::HTML, 'SPAN');
        $this->assertEquals(Namespaces::HTML, $element->namespaceURI);
        $this->assertNull($element->prefix);
        $this->assertEquals('SPAN', $element->localName);
        $this->assertEquals('SPAN', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertInstanceOf(HTMLElement::class, $element);
        $this->assertInstanceOf(HTMLUnknownElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }

    /**
     * Upper-case HTML element with a prefix.
     */
    public function test7(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS(Namespaces::HTML, 'html:SPAN');
        $this->assertEquals(Namespaces::HTML, $element->namespaceURI);
        $this->assertEquals('html', $element->prefix);
        $this->assertEquals('SPAN', $element->localName);
        $this->assertEquals('HTML:SPAN', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertInstanceOf(HTMLElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }

    /**
     * Upper-case non-HTML element without a prefix.
     */
    public function test8(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS('test', 'SPAN');
        $this->assertEquals('test', $element->namespaceURI);
        $this->assertNull($element->prefix);
        $this->assertEquals('SPAN', $element->localName);
        $this->assertEquals('SPAN', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertNotInstanceOf(HTMLElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }

    /**
     * Upper-case non-HTML element with a prefix.
     */
    public function test9(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS('test', 'html:SPAN');
        $this->assertEquals('test', $element->namespaceURI);
        $this->assertEquals('html', $element->prefix);
        $this->assertEquals('SPAN', $element->localName);
        $this->assertEquals('html:SPAN', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertNotInstanceOf(HTMLElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }

    public function testNullNamespace(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS(null, 'span');
        $this->assertNull($element->namespaceURI);
        $this->assertNull($element->prefix);
        $this->assertEquals('span', $element->localName);
        $this->assertEquals('span', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertNotInstanceOf(HTMLElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }

    public function testEmptyStringNamespace(): void
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElementNS('', 'span');
        $this->assertNull($element->namespaceURI);
        $this->assertNull($element->prefix);
        $this->assertEquals('span', $element->localName);
        $this->assertEquals('span', $element->tagName);
        $this->assertInstanceOf(Node::class, $element);
        $this->assertInstanceOf(Element::class, $element);
        $this->assertNotInstanceOf(HTMLElement::class, $element);
        $this->assertNotInstanceOf(HTMLSpanElement::class, $element);
    }
}
