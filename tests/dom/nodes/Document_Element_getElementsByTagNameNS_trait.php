<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeList;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-Element-getElementsByTagNameNS.js
 */
trait Document_Element_getElementsByTagNameNS_trait
{
    use WindowTrait;

    abstract public static function context(): Node;

    abstract public static function element(): Element;

    public function testCachingIsAllowed(): void
    {
        self::assertNotInstanceOf(NodeList::class, self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "html"));
        self::assertInstanceOf(HTMLCollection::class, self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "html"));
        $firstCollection = self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "html");
        $secondCollection = self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "html");
        self::assertTrue($firstCollection !== $secondCollection || $firstCollection === $secondCollection);
    }

    public function testGetElementsByTagNameNSWildcardNamespace(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'body'));
        $actual = self::context()->getElementsByTagNameNS('*', 'body');
        $expected = [];
        $get_elements = static function (Node $node) use (&$expected, &$get_elements): void {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === $child::ELEMENT_NODE) {
                    if ($child->localName === 'body') {
                        $expected[] = $child;
                    }

                    $get_elements($child);
                }
            }
        };

        $get_elements(self::context());
        self::assertSame($expected, iterator_to_array($actual));
        self::element()->removeChild($t);
    }

    public function testEmptyStringNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS('', '*')));
        $t = self::element()->appendChild($document->createElementNS('', 'body'));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS('', '*')));
        self::element()->removeChild($t);
    }

    public function testBodyElementInTestNamespaceNoPrefix(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'body'));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS('test', 'body')));
        self::element()->removeChild($t);
    }

    public function testBodyElementInTestNamespacePrefix(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'test:body'));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS('test', 'body')));
        self::element()->removeChild($t);
    }

    public function testUppercaseBodyElementInTestNamespaceNoPrefix(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'BODY'));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS('test', 'BODY')));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS('test', 'body')));
        self::element()->removeChild($t);
    }

    public function testAbcElementHtmltNamespace(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS("http://www.w3.org/1999/xhtml", "abc"));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "abc")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "ABC")));
        self::element()->removeChild($t);
    }

    public function testUppercaseAbcElementHtmltNamespace(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS("http://www.w3.org/1999/xhtml", "ABC"));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "abc")));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "ABC")));
        self::element()->removeChild($t);
    }

    public function testAcCaseSensitivity(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS("http://www.w3.org/1999/xhtml", "AÇ"));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "AÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "aÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "aç")));
        self::element()->removeChild($t);
    }

    public function testUppercaseBodyElementInTestNamespacePrefix(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS("test", "test:BODY"));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagNameNS("test", "BODY")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS('test', 'body')));
        self::element()->removeChild($t);
    }

    public function testGetElementsByTagNameNSWildcardLocalName(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'test:test'));
        $actual = self::context()->getElementsByTagNameNS("http://www.w3.org/1999/xhtml", "*");
        $expected = [];
        $get_elements = static function (Node $node) use (&$expected, &$get_elements, $t): void {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === $child::ELEMENT_NODE) {
                    if ($child !== $t) {
                        $expected[] = $child;
                    }

                    $get_elements($child);
                }
            }
        };

        $get_elements(self::context());
        self::assertSame($expected, iterator_to_array($actual));
        self::element()->removeChild($t);
    }

    public function testGetElementsByTagNameNSWildcardNamespaceAndLocalName(): void
    {
        $actual = self::context()->getElementsByTagNameNS("*", "*");
        $expected = [];
        $get_elements = static function (Node $node) use (&$expected, &$get_elements): void {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === $child::ELEMENT_NODE) {
                    $expected[] = $child;
                    $get_elements($child);
                }
            }
        };

        $get_elements(self::context());
        self::assertSame($expected, iterator_to_array($actual));
    }

    public function testEmptyLists(): void
    {
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS("**", "*")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS(null, "0")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagNameNS(null, "div")));
    }

    public function testGetElementsByTagNameNSSholdBeALiveCollection(): void
    {
        $document = self::getWindow()->document;
        $t1 = self::element()->appendChild($document->createElementNS("test", "abc"));
        $l = self::context()->getElementsByTagNameNS("test", "abc");

        self::assertInstanceOf(HTMLCollection::class, $l);
        self::assertSame(1, $l->length);

        $t2 = self::element()->appendChild($document->createElementNS("test", "abc"));
        self::assertSame(2, $l->length);

        self::element()->removeChild($t2);
        self::assertSame(1, $l->length);

        self::element()->removeChild($t1);
    }
}
