<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\documents\dom_tree_accessors;

use Generator;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/documents/dom-tree-accessors/document.title-05.html
 */
class DocumentTitle05Test extends AccessorTestCase
{
    use WindowTrait;

    private CONST WHITESPACE = [
        "\u{000B}",
        "\u{0085}",
        "\u{00A0}",
        "\u{1680}",
        "\u{180E}",
        "\u{2000}",
        "\u{2001}",
        "\u{2002}",
        "\u{2003}",
        "\u{2004}",
        "\u{2005}",
        "\u{2006}",
        "\u{2007}",
        "\u{2008}",
        "\u{2009}",
        "\u{200A}",
        "\u{2028}",
        "\u{2029}",
        "\u{202F}",
        "\u{205F}",
        "\u{3000}",
    ];

    /**
     * @dataProvider whitespaceProvider
     */
    public function testSetTitle(string $character, int $i): void
    {
        $document = self::getWindow()->document;
        $s = $character . "a" . $character . $character . "b" . $character . "c" . $i . $character;
        $document->title = $s;
        self::assertSame($s, $document->title);
    }

    public function whitespaceProvider(): Generator
    {
        foreach (self::WHITESPACE as $i => $char) {
            yield [$char, $i];
        }
    }

    public static function getDocumentName(): string
    {
        return 'document.title-05.html';
    }
}
