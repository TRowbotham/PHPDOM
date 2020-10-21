<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.title-03.html
 */
class DocumentTitle03Test extends AccessorTestCase
{
    use WindowTrait;

    public function testDocumentTitleIntialValue(): void
    {
        // Single space characters must be normalized. (WHATWG r4353)
        self::assertSame(
            "document.title and space normalization",
            self::getWindow()->document->title
        );
    }

    /**
     * @dataProvider titleProvider
     */
    public function testSetTitle(string $set, string $expected): void
    {
        $document = self::getWindow()->document;
        $document->title = $set;
        self::assertSame($expected, $document->title);
    }

    public function titleProvider(): array
    {
        return [
            ["one space", "one space"],
            ["two  spaces", "two spaces"],
            ["two  spaces", "two spaces"],
            ["one\ttab", "one tab"],
            ["two\t\ttabs", "two tabs"],
            ["one\nnewline", "one newline"],
            ["two\n\nnewlines", "two newlines"],
            ["one\fform feed", "one form feed"],
            ["two\f\fform feeds", "two form feeds"],
            ["one\rcarriage return", "one carriage return"],
            ["two\r\rcarriage returns", "two carriage returns"],
        ];
    }

    public static function getDocumentName(): string
    {
        return 'document.title-03.html';
    }
}
