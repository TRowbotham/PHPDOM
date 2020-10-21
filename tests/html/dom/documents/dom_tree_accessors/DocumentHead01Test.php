<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.head-01.html
 */
class DocumentHead01Test extends AccessorTestCase
{
    use WindowTrait;

    public function testDocumentHead(): void
    {
        $document = self::getWindow()->document;
        $head = $document->getElementsByTagName('head')[0];
        self::assertSame($head, $document->head);
        $document->head = '';
        self::assertSame($head, $document->head);
        $document->head = $document->createElement('head');
        self::assertSame($head, $document->head);
        $document->documentElement->appendChild($document->createElement('head'));
        self::assertSame($head, $document->head);
        $head2 = $document->createElement('head');
        $document->documentElement->insertBefore($head2, $head);
        self::assertSame($head2, $document->head);
    }

    public static function getDocumentName(): string
    {
        return 'document.head-01.html';
    }
}
