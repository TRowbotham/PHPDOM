<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Text;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.title-09.html
 */
class DocumentTitle09Test extends AccessorTestCase
{
    use DocumentGetter;

    public function testNoTitleElementInSVGDocument(): void
    {
        $doc = $this->newSVGDocument();
        self::assertSame('', $doc->title);
        $child = $doc->createElementNS(Namespaces::SVG, 'x-child');
        $doc->documentElement->appendChild($child);
        $doc->title = 'foo';
        self::assertIsSVGTitle('foo', $doc->documentElement->firstChild);
        self::assertSame('foo', $doc->title);
    }

    public function testTitleElementInSVGDocument(): void
    {
        $doc = $this->newSVGDocument();
        $title = $doc->createElementNS(Namespaces::SVG, 'title');
        $title->textContent = 'foo';
        $doc->documentElement->appendChild($title);
        self::assertSame('foo', $doc->title);
        $doc->title .= 'bar';
        self::assertSame('foobar', $title->textContent);
        self::assertSame(1, $title->childNodes->length);
        self::assertInstanceOf(Text::class, $title->childNodes[0]);
        self::assertSame('foobar', $doc->title);
        $doc->title = '';
        self::assertSame('', $title->textContent);
        self::assertSame('', $doc->title);
        self::assertSame(0, $title->childNodes->length);
    }

    public function testTitleElementNotChildOfSVGRoot(): void
    {
        $doc = $this->newSVGDocument();
        $title = $doc->createElementNS(Namespaces::SVG, 'title');
        $title->textContent = 'foo';
        $child = $doc->createElementNS(Namespaces::SVG, 'x-child');
        $child->appendChild($title);
        $doc->documentElement->appendChild($child);
        self::assertSame('', $doc->title);

        // Now test that on setting, we create a new element and don't change the
        // existing one
        $doc->title = 'bar';
        self::assertSame('foo', $title->textContent);
        self::assertIsSVGTitle('bar', $doc->documentElement->firstChild);
        self::assertSame('bar', $doc->title);
    }

    public function testTitleElementNotInSVGNamespace(): void
    {
        $doc = $this->newSVGDocument();
        $title = $doc->createElementNS(Namespaces::HTML, 'title');
        $title->textContent = 'foo';
        $doc->documentElement->appendChild($title);
        self::assertSame('', $doc->title);
    }

    public function testRootElementNotNamedSVG(): void
    {
        // "SVG" != "svg"
        $doc = $this->getHTMLDocument()->implementation->createDocument(Namespaces::SVG, 'SVG', null);

        // Per spec, this does nothing
        $doc->title = 'foo';
        self::assertSame(0, $doc->documentElement->childNodes->length);
        self::assertSame('', $doc->title);

        // An SVG title is ignored by .title
        $doc->documentElement->appendChild($doc->createElementNS(Namespaces::SVG, 'title'));
        $doc->documentElement->lastChild->textContent = 'foo';
        self::assertSame('', $doc->title);

        // But an HTML title is respected
        $doc->documentElement->appendChild($doc->createElementNS(Namespaces::HTML, 'title'));
        $doc->documentElement->lastChild->textContent = 'bar';
        self::assertSame('bar', $doc->title);

        // Even if it's not a child of the root
        $div = $doc->createElementNS(Namespaces::HTML, 'div');
        $div->appendChild($doc->documentElement->lastChild);
        $doc->documentElement->appendChild($div);
        self::assertSame('bar', $doc->title);
    }

    public static function assertIsSVGTitle(string $expectedText, Element $element): void
    {
        self::assertSame(Namespaces::SVG, $element->namespaceURI);
        self::assertSame('title', $element->localName);
        self::assertSame($expectedText, $element->textContent);
    }

    private function newSVGDocument()
    {
        return $this->getHTMLDocument()->implementation->createDocument(Namespaces::SVG, 'svg', null);
    }
}
