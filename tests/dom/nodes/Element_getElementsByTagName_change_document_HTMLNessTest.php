<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-getElementsByTagName-change-document-HTMLNess.html
 */
class Element_getElementsByTagName_change_document_HTMLNessTest extends NodeTestCase
{
    use WindowTrait;

    public function testChangeNodeDocument(): void
    {
        $this->markTestSkipped('We don\'t support iframes yet');

        $document = self::getWindow()->document;
        $parent = $document->createElement('div');
        $child1 = $document->createElementNS("http://www.w3.org/1999/xhtml", "a");
        $child1->textContent = 'xhtml:a';
        $child2 = $document->createElementNS("http://www.w3.org/1999/xhtml", "A");
        $child2->textContent = "xhtml:A";
        $child3 = $document->createElementNS("", "a");
        $child3->textContent = "a";
        $child4 = $document->createElementNS("", "A");
        $child4->textContent = "A";

        $parent->appendChild($child1);
        $parent->appendChild($child2);
        $parent->appendChild($child3);
        $parent->appendChild($child4);

        $list = $parent->getElementsByTagName('A');
        self::assertSame([$child1, $child4], iterator_to_array($list));

        $frames[0]->document->documentElement->appendChild($parent);
        self::assertSame([$child1, $child4], iterator_to_array($list));

        self::assertSame([$child2, $child4], iterator_to_array($parent->getElementsByTagName('A')));

        // Now reinsert all those nodes into the parent, to blow away caches.
        $parent->appendChild($child1);
        $parent->appendChild($child2);
        $parent->appendChild($child3);
        $parent->appendChild($child4);

        self::assertSame([$child1, $child4], iterator_to_array($list));
        self::assertSame([$child2, $child4], iterator_to_array($parent->getElementsByTagName('A')));
    }

    public static function getDocumentName(): string
    {
        return 'Element-getElementsByTagName-change-document-HTMLNess.html';
    }
}
