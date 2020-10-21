<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.title-05.html
 */
class DocumentTitle08Test extends AccessorTestCase
{
    use WindowTrait;

    public function testNoTitleElement(): void
    {
        self::assertSame('', self::getWindow()->document->title);
    }

    public function testTitleElementContainsMultipleChildTextNodes(): void
    {
        $document = self::getWindow()->document;
        $title = $document->createElement("title");
        $title->appendChild($document->createTextNode("PASS"));
        $document->head->appendChild($title);
        self::assertSame("PASS", $document->title);

        $title->appendChild($document->createTextNode("PASS2"));
        $title->appendChild($document->createTextNode("PASS3"));
        self::assertSame("PASSPASS2PASS3", $document->title);
    }

    public static function getDocumentName(): string
    {
        return 'document.title-08.html';
    }
}
