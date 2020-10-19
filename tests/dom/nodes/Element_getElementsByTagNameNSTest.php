<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Node;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-getElementsByTagNameNS.html
 */
class Element_getElementsByTagNameNSTest extends NodeTestCase
{
    use Document_Element_getElementsByTagNameNS_trait;

    public function testMatchingContextObjectWildcardNamespace(): void
    {
        self::assertSame(
            [],
            iterator_to_array(self::element()->getElementsByTagNameNS('*', self::element()->localName))
        );
    }

    public function testMatchingContextObjectSpecificNamespace(): void
    {
        self::assertSame(
            [],
            iterator_to_array(self::element()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", self::element()->localName))
        );
    }

    public static function runSetup(): Element
    {
        static $element;

        if ($element !== null) {
            return $element;
        }

        $document = self::getWindow()->document;
        $element = $document->createElement('div');
        $element->appendChild($document->createTextNode("text"));
        $p = $element->appendChild($document->createElement("p"));
        $p->appendChild($document->createElement("a"))
            ->appendChild($document->createTextNode("link"));
        $p->appendChild($document->createElement("b"))
            ->appendChild($document->createTextNode("bold"));
        $p->appendChild($document->createElement("em"))
            ->appendChild($document->createElement("u"))
            ->appendChild($document->createTextNode("emphasized"));
        $element->appendChild($document->createComment("comment"));

        return $element;
    }

    public static function context(): Node
    {
        return self::runSetup();
    }

    public static function element(): Element
    {
        return self::runSetup();
    }

    public static function getDocumentName(): string
    {
        return 'Element-getElementsByTagNameNS.html';
    }
}
