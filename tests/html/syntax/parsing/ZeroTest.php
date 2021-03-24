<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/zero.html
 */
class ZeroTest extends TestCase
{
    use WindowTrait;

    public function testNullShouldGetReplacedWithReplacementCharAfterMarkupDeclarationHyphen(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "<!-\u{0000}>";
        self::assertSame("-\u{FFFD}", $div->firstChild->data);
    }

    public function testNullShouldVanishAfterAmpersand(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "&a\u{0000}uml;";
        self::assertSame('&auml;', $div->firstChild->data);
    }

    public function testNullShouldVanishAfterAmpersandAndOneLetterOfEntityPrefix(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "&au\u{0000}ml;";
        self::assertSame('&auml;', $div->firstChild->data);
    }

    public function testNullShouldVanishAfterAmpersandAndThreeLettersOfEntityPrefix(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "&aum\u{0000}l;";
        self::assertSame('&auml;', $div->firstChild->data);
    }

    public function testNullShouldVanishAfterSemicolonlessEntity(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "&auml\u{0000};";
        self::assertSame("\u{00E4};", $div->firstChild->data);
    }

    public function testNullShouldVanishBeforeRequiredSemicolon(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "&notin\u{0000};";
        self::assertSame("\u{00AC}in;", $div->firstChild->data);
    }

    public function testNullShouldGetReplacedWithReplacementCharAfterAmpersand(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "<span title='&\u{0000}auml;'>";
        self::assertSame("&\u{FFFD}auml;", $div->firstChild->title);
    }

    public function testNullShouldGetReplacedWithReplacementCharAfterAmpersandAndOneLetterOfEntityPrefix(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "<span title='&a\u{0000}uml;'>";
        self::assertSame("&a\u{FFFD}uml;", $div->firstChild->title);
    }

    public function testNullShouldGetReplacedWithReplacementCharAfterAmpersandAndTwoLettersOfEntityPrefix(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "<span title='&au\u{0000}ml;'>";
        self::assertSame("&au\u{FFFD}ml;", $div->firstChild->title);
    }

    public function testNullShouldGetReplacedWithReplacementCharAfterAmpersandAndThreeLettersOfEntityPrefix(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "<span title='&aum\u{0000}l;'>";
        self::assertSame("&aum\u{FFFD}l;", $div->firstChild->title);
    }

    public function testNullShouldGetReplacedWithReplacementCharAfterSemicolonlessEntity(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "<span title='&auml\u{0000};'>";
        self::assertSame("\u{00E4}\u{FFFD};", $div->firstChild->title);
    }

    public function testNullShouldGetReplacedWithReplacementCharBeforeRequiredSemicolon(): void
    {
        $div = self::getWindow()->document->getElementsByTagName('div')[0];
        $div->innerHTML = "<span title='&notin\u{0000};'>";
        self::assertSame("&notin\u{FFFD};", $div->firstChild->title);
    }

    public static function getDocumentName(): string
    {
        return 'zero.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
