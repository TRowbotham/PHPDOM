<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.title-01.html
 */
class DocumentTitle01Test extends AccessorTestCase
{
    use WindowTrait;

    public function test1(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('document.title with head blown away', $document->title);
    }

    public function test2(): void
    {
        $document = self::getWindow()->document;
        $head = $document->getElementsByTagName('head')[0];
        self::assertNotNull($head);
        $head->parentNode->removeChild($head);
        self::assertNull($document->getElementsByTagName('head')[0]);
        $document->title = 'FAIL';
        self::assertSame('', $document->title);
    }

    public function test3(): void
    {
        $document = self::getWindow()->document;
        $title2 = $document->createElement('title');
        $title2->appendChild($document->createTextNode('PASS'));
        $document->body->appendChild($title2);
        self::assertSame('PASS', $document->title);
    }

    public function test4(): void
    {
        $document = self::getWindow()->document;
        $title3 = $document->createElement('title');
        $title3->appendChild($document->createTextNode('PASS2'));
        $document->documentElement->insertBefore($title3, $document->body);
        self::assertSame('PASS2', $document->title);
    }

    public static function getDocumentName(): string
    {
        return 'document.title-01.html';
    }
}
