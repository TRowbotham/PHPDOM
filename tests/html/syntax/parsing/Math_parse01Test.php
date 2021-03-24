<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/math-parse01.html
 */
class Math_parse01Test extends TestCase
{
    use WindowTrait;

    public function testIdAttributeShouldBeRecognizedOnMathElements(): void
    {
        $document = self::getWindow()->document;
        self::assertSame($document->getElementsByTagName('math')[0], $document->getElementById('m1'));
    }

    public function testNodeNameShouldBeMath(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('math', $document->getElementById('d1')->firstChild->nodeName);
    }

    public function testMathShouldBeInMathMLNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(Namespaces::MATHML, $document->getElementById('d1')->firstChild->namespaceURI);
    }

    public function testMathHas2Children(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(2, $document->getElementById('d1')->firstChild->childNodes->length);
    }

    public function testNestedMrowElementsShouldBeParsedCorrectly(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(1, $document->getElementById('d2')->firstChild->childNodes->length);
    }

    public function testRangAndLangEntityCodePoints(): void
    {
        $document = self::getWindow()->document;
        self::assertSame("\u{27E8}\u{27E9}", $document->getElementById('d3')->firstChild->nodeValue);
    }

    public function testKpofPlane1EntityCodePoint(): void
    {
        $document = self::getWindow()->document;
        self::assertSame("\u{1D542}", $document->getElementById('d4')->firstChild->nodeValue);
    }

    public function testEmptyElementTagsInAnnotationXmlParsedAsPerXML(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(2, $document->getElementById('d5')->firstChild->firstChild->childNodes[1]->childNodes->length);
    }

    public function testHtmlTagsAllowedInAnnotationXml(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(2, $document->getElementById('d6')->firstChild->childNodes->length);
    }

    public static function getDocumentName(): string
    {
        return 'math-parse01.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
