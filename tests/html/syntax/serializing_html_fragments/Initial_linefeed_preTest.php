<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\serializing_html_fragments;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function str_replace;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/serializing-html-fragments/initial-linefeed-pre.html
 */
class Initial_linefeed_preTest extends TestCase
{
    use WindowTrait;

    private const EXPECTED_OUTER = "\n<div id=\"inner\">\n<pre id=\"pre1\">x</pre>\n<pre id=\"pre2\">\nx</pre>\n<textarea id=\"textarea1\">x</textarea>\n<textarea id=\"textarea2\">\nx</textarea>\n<listing id=\"listing1\">x</listing>\n<listing id=\"listing2\">\nx</listing>\n</div>\n";

    public function testOuterDiv(): void
    {
        $outer = self::getWindow()->document->getElementById('outer');
        self::assertSame(self::EXPECTED_OUTER, $outer->innerHTML);
    }

    public function testInnerDiv(): void
    {
        $expected_inner = str_replace(["\n<div id=\"inner\">", "</div>\n"], '', self::EXPECTED_OUTER);
        $inner = self::getWindow()->document->getElementById('inner');
        self::assertSame($expected_inner, $inner->innerHTML);
    }

    /**
     * @dataProvider tagProvider
     */
    public function testTag1(string $tag): void
    {
        self::assertSame('x', self::getWindow()->document->getElementById($tag . '1')->innerHTML);
    }

    /**
     * @dataProvider tagProvider
     */
    public function testTag2(string $tag): void
    {
        self::assertSame("\nx", self::getWindow()->document->getElementById($tag . '2')->innerHTML);
    }

    public function tagProvider(): array
    {
        return [
            ["pre"],
            ["textarea"],
            ["listing"],
        ];
    }

    public static function getDocumentName(): string
    {
        return 'initial-linefeed-pre.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
