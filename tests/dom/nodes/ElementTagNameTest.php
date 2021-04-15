<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Element-tagName.html
 */
class ElementTagNameTest extends TestCase
{
    use DocumentGetter;

    /**
     * tagName should upper-case for HTML elements in HTML documents.
     */
    public function test1()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals('I', $document->createElementNS(
            Namespaces::HTML,
            'I'
        )->tagName);
        $this->assertEquals('I', $document->createElementNS(
            Namespaces::HTML,
            'i'
        )->tagName);
        $this->assertEquals('X:B', $document->createElementNS(
            Namespaces::HTML,
            'x:b'
        )->tagName);
    }

    /**
     * tagName should not upper-case for SVG elements in HTML documents.
     */
    public function test2()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals('svg', $document->createElementNS(
            Namespaces::SVG,
            'svg'
        )->tagName);
        $this->assertEquals('SVG', $document->createElementNS(
            Namespaces::SVG,
            'SVG'
        )->tagName);
        $this->assertEquals('s:svg', $document->createElementNS(
            Namespaces::SVG,
            's:svg'
        )->tagName);
        $this->assertEquals('S:SVG', $document->createElementNS(
            Namespaces::SVG,
            'S:SVG'
        )->tagName);
    }

    /**
     * tagName should be updated when changing ownerDocument.
     */
    public function test3()
    {
        $this->markTestSkipped('We don\'t support parsing xml documents yet.');
        $document = $this->getHTMLDocument();
        $xmlel = (new DOMParser())
            ->parseFromString(
                '<div xmlns="http://www.w3.org/1999/xhtml">Test</div>',
                'text/xml'
            )
            ->documentElement;
        $this->assertEquals('div', $xmlel->tagName);
        $htmlel = $document->importNode($xmlel, true);
        $this->assertEquals('DIV', $htmlel->tagName);
    }

    /**
     * tagName should be updated when changing ownerDocument (createDocuemnt
     * without prefix).
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $xmlel = $document->implementation->createDocument(
            "http://www.w3.org/1999/xhtml",
            "div",
            null
        )->documentElement;
        $this->assertEquals('div', $xmlel->tagName);
        $htmlel = $document->importNode($xmlel, true);
        $this->assertEquals('DIV', $htmlel->tagName);
    }

    /**
     * tagName should be updated when changing ownerDocument (createDocument)\
     * with prefix).
     */
    public function test5()
    {
        $document = $this->getHTMLDocument();
        $xmlel = $document->implementation->createDocument(
            "http://www.w3.org/1999/xhtml",
            "foo:div",
            null
        )->documentElement;
        $this->assertEquals('foo:div', $xmlel->tagName);
        $htmlel = $document->importNode($xmlel, true);
        $this->assertSame($document, $htmlel->ownerDocument);
        $this->assertEquals('FOO:DIV', $htmlel->tagName);
    }
}
