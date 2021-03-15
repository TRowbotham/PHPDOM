<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\SVG\SVGSVGElement;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Node-cloneNode-svg.html
 */
class Node_cloneNode_svgTest extends NodeTestCase
{
    use WindowTrait;

    public function testClonedSvgShouldHaveTheRightProperties(): void
    {
        $clone = $this->getClone();

        self::assertSame("http://www.w3.org/2000/svg", $clone->namespaceURI);
        self::assertNull($clone->prefix);
        self::assertSame('svg', $clone->localName);
        self::assertSame('svg', $clone->tagName);
    }

    public function testClonedSvgXmlnsXlinkAttributeShouldHaveTheRightProperties(): void
    {
        $attr = $this->getClone()->attributes[0];

        self::assertSame("http://www.w3.org/2000/xmlns/", $attr->namespaceURI);
        self::assertSame('xmlns', $attr->prefix);
        self::assertSame('xlink', $attr->localName);
        self::assertSame('xmlns:xlink', $attr->name);
        self::assertSame("http://www.w3.org/1999/xlink", $attr->value);
    }

    public function testClonedUseShouldHaveTheRightProperties(): void
    {
        $use = $this->getClone()->firstElementChild;

        self::assertSame("http://www.w3.org/2000/svg", $use->namespaceURI);
        self::assertNull($use->prefix);
        self::assertSame('use', $use->localName);
        self::assertSame('use', $use->tagName);
    }

    public function testClonedUseXlinkHrefAttributeShouldHaveTheRightProperties(): void
    {
        $use = $this->getClone()->firstElementChild;
        $attr = $use->attributes[0];

        self::assertSame("http://www.w3.org/1999/xlink", $attr->namespaceURI);
        self::assertSame('xlink', $attr->prefix);
        self::assertSame('href', $attr->localName);
        self::assertSame('xlink:href', $attr->name);
        self::assertSame("#test", $attr->value);
    }

    public function getClone(): SVGSVGElement
    {
        $document = self::getWindow()->document;
        // $svg = $document->querySelector('svg');
        $svg = $document->getElementsByTagName('svg')[0];
        $clone = $svg->cloneNode(true);

        return $clone;
    }

    public static function getDocumentName(): string
    {
        return 'Node-cloneNode-svg.html';
    }
}
