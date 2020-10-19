<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\HTMLCollection;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeList;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-Element-getElementsByTagName.js
 */
trait DocumentElementGetElementsByTagNameTrait
{
    use WindowTrait;

    abstract public static function context(): Node;
    abstract public static function element(): Element;

    public function testInterfaces(): void
    {
        self::assertNotInstanceOf(NodeList::class, self::context()->getElementsByTagName('html'));
        self::assertInstanceOf(HTMLCollection::class, self::context()->getElementsByTagName('html'));
    }

    public function testCachingIsAllowed(): void
    {
        $firstCollection = self::context()->getElementsByTagName('html');
        $secondCollection = self::context()->getElementsByTagName('html');

        self::assertTrue(
            $firstCollection !== $secondCollection || $firstCollection === $secondCollection
        );
    }

    public function testShouldntBeAbleToSetUnsignedProperties(): void
    {
        $l = self::context()->getElementsByTagName('nosuchtag');

        self::assertThrows(static function () use ($l): void {
            $l[5] = 'foopy';
        }, TypeError::class);
        self::assertNull($l[5]);
        self::assertNull($l->item(5));
    }

    public function testHTMLElementWithUppercaseTagNameNeverMatchesInHTMLDocuments(): void
    {
        $document = self::getWindow()->document;

        self::assertSame('i', $document->createElementNS("http://www.w3.org/1999/xhtml", 'i')->localName); // Sanity
        $t = self::element()->appendChild($document->createElementNS("http://www.w3.org/1999/xhtml", 'I'));
        self::assertSame('I', $t->localName);
        self::assertSame('I', $t->tagName);
        self::assertSame(0, self::context()->getElementsByTagName('I')->length);
        self::assertSame(0, self::context()->getElementsByTagName('i')->length);
        self::element()->removeChild($t);
    }

    public function testElementInNonHTMLNamespaceNoPrefixLowercaseName(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'st'));

        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName('st')));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('ST')));
        self::element()->removeChild($t);
    }

    public function testElementInNonHTMLNamespaceNoPrefixUppercaseName(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'ST'));

        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName('ST')));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('st')));
        self::element()->removeChild($t);
    }

    public function testElementInNonHTMLNamespacePrefixLowercaseName(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'te:st'));

        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('st')));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('ST')));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName('te:st')));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('te:ST')));
        self::element()->removeChild($t);
    }

    public function testElementInNonHTMLNamespacePrefixUppercaseName(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', 'te:ST'));

        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('st')));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('ST')));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName('te:st')));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName('te:ST')));
        self::element()->removeChild($t);
    }

    public function testElementInHTMLNamespaceNoPrefixNonAscii(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElement("aÇ"));

        self::assertSame("aÇ", $t->localName);
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName("AÇ")));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName("aÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName("aç")));
        self::element()->removeChild($t);
    }

    public function testElementInNonHTMLNamespaceNonAscii(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS('test', "AÇ"));

        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName("AÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName("aÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName("aç")));
        self::element()->removeChild($t);
    }

    public function testElementInHTMLNamespacePrefixNonAsciiLowercase(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS("http://www.w3.org/1999/xhtml", "test:aÇ"));

        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName("TEST:AÇ")));
        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName("test:aÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName("test:aç")));
        self::element()->removeChild($t);
    }

    public function testElementInHTMLNamespacePrefixNonAsciiUppercase(): void
    {
        $document = self::getWindow()->document;
        $t = self::element()->appendChild($document->createElementNS("test", "TEST:AÇ"));

        self::assertSame([$t], iterator_to_array(self::context()->getElementsByTagName("TEST:AÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName("test:aÇ")));
        self::assertSame([], iterator_to_array(self::context()->getElementsByTagName("test:aç")));
        self::element()->removeChild($t);
    }

    public function testGetElementsByTagNameWildcardQualifiedName(): void
    {
        $actual = self::context()->getElementsByTagName('*');
        $expected = [];
        $get_elements = static function ($node) use (&$expected, &$get_elements) {
            for ($i = 0; $i < $node->childNodes->length; ++$i) {
                $child = $node->childNodes[$i];

                if ($child->nodeType === $child::ELEMENT_NODE) {
                    $expected[] = $child;
                    $get_elements($child);
                }
            }
        };

        $get_elements(self::context());
        self::assertSame($expected, iterator_to_array($actual));
    }

    public function testGetElementsByTagNameShouldBeALiveCollection(): void
    {
        $document = self::getWindow()->document;

        $t1 = self::element()->appendChild($document->createElement('abc'));
        $l = self::context()->getElementsByTagName('abc');
        self::assertInstanceOf(HTMLCollection::class, $l);
        self::assertSame(1, $l->length);

        $t2 = self::element()->appendChild($document->createElement('abc'));
        self::assertSame(2, $l->length);

        self::element()->removeChild($t2);
        self::assertSame(1, $l->length);
        self::element()->removeChild($t1);
    }
}
