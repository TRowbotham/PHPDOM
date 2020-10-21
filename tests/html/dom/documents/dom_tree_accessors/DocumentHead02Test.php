<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.head-02.html
 */
class DocumentHead02Test extends AccessorTestCase
{
    use WindowTrait;

    public function testDocumentHead(): void
    {
        $document = self::getWindow()->document;
        $head = $document->getElementsByTagName('head')[0];
        self::assertSame($head, $document->head);
        $head2 = $document->createElementNS(Namespaces::HTML, "blah:head");
        $document->documentElement->insertBefore($head2, $head);
        self::assertSame($head2, $document->head);
        $head3 = $document->createElementNS("http://www.example.org/", "blah:head");
        $document->documentElement->insertBefore($head3, $head2);
        self::assertSame($head2, $document->head);
    }

    public static function getDocumentName(): string
    {
        return 'document.head-02.html';
    }
}
