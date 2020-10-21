<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Element\HTML\HTMLTitleElement;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.title-05.html
 */
class DocumentTitle06Test extends AccessorTestCase
{
    use WindowTrait;

    public function testSetTitle(): void
    {
        $document = self::getWindow()->document;
        $head = $document->documentElement->firstChild;
        $head->removeChild($head->firstChild);
        self::assertSame('', $document->title);
        $document->title = '';
        self::assertSame('', $document->title);
        self::assertInstanceOf(HTMLTitleElement::class, $head->lastChild);
        self::assertNull($head->lastChild->firstChild);
    }

    public static function getDocumentName(): string
    {
        return 'document.title-06.html';
    }
}
