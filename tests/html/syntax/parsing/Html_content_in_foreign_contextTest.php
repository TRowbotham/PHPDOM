<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Generator;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

use function substr;

class Html_content_in_foreign_contextTest extends TestCase
{
    private const CONTEXTS = ["svg", "math"];
    private const ELEMENTS = ["/p", "/br", "b", "big", "blockquote", "br", "center", "code", "dd", "div", "dl", "dt", "em", "embed", "h1", "h2", "h3", "h4", "h5", "h6", "hr", "i", "img", "li", "listing", "menu", "meta", "nobr", "ol", "p", "pre", "ruby", "s", "small", "span", "strong", "strike", "sub", "sup", "table", "tt", "u", "ul", "var"];

    /**
     * @dataProvider elementProvider
     */
    public function testHTMLNamespaceNodesShouldExitForeignContent(string $c, string $e): void
    {
        $document = new HTMLDocument();
        $wrapper = $document->createElement('div');
        $html = "<{$c}><$e></{$c}";
        $wrapper->innerHTML = $html;
        self::assertNotSame($html, $wrapper->innerHTML);

        $tagname = $e[0] === '/' ? substr($e, 1) : $e;
        $element = $wrapper->getElementsByTagName($tagname)[0];
        self::assertNotNull($element);
        $parent = $element->parentNode;
        self::assertSame($wrapper, $element->parentNode);
    }

    public function elementProvider(): Generator
    {
        foreach (self::CONTEXTS as $context) {
            foreach (self::ELEMENTS as $element) {
                yield [$context, $element];
            }
        }
    }
}
