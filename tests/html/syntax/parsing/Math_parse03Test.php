<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/math-parse03.html
 */
class Math_parse03Test extends TestCase
{
    use WindowTrait;

    public function testMATHElementNameShouldBeLowercased(): void
    {
        $document = self::getWindow()->document;
        self::assertSame($document->getElementsByTagName('math')[0], $document->getElementById('m1'));
    }

    public function testMIElementNameAndMathvariantAttributeNameShouldBeLowercasedAttributeValueUnchanged(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('mi', $document->getElementById('d1')->firstChild->firstChild->nodeName);
        self::assertSame(Namespaces::MATHML, $document->getElementById('d1')->firstChild->firstChild->namespaceURI);
        self::assertTrue($document->getElementById('d1')->firstChild->firstChild->hasAttribute('mathvariant'));
        self::assertSame('BOLD', $document->getElementById('d1')->firstChild->firstChild->getAttribute('mathvariant'));
    }

    public function testDEFINITIONurlAttributeMarkupShouldProduceAdefinitionURLAttributeAttributeValueUnchanged(): void
    {
        $document = self::getWindow()->document;
        self::assertTrue($document->getElementById('d2')->firstChild->firstChild->hasAttribute('definitionURL'));
        self::assertSame("www.example.org/FOO", $document->getElementById('d2')->firstChild->firstChild->getAttribute('definitionURL'));
    }

    public function testHtmlSpanInMtextProducesSPANNodeNameInXHTMLNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('SPAN', $document->getElementById('m3span-mtext')->firstChild->firstChild->nodeName);
        self::assertSame(Namespaces::HTML, $document->getElementById('m3span-mtext')->firstChild->firstChild->namespaceURI);
    }

    public function testHtmlSpanInMiProducesSPANNodeNameInXHTMLNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('SPAN', $document->getElementById('m3span-mi')->firstChild->firstChild->nodeName);
        self::assertSame(Namespaces::HTML, $document->getElementById('m3span-mi')->firstChild->firstChild->namespaceURI);
    }

    public function testHtmlSpanInMrowProducesSPANNodeNameInXHTMLNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('SPAN', $document->getElementById('m3span-mrow')->firstChild->firstChild->nodeName);
        self::assertSame(Namespaces::HTML, $document->getElementById('m3span-mrow')->firstChild->firstChild->namespaceURI);
    }

    public function testHtmlPInMiProducesPNodeNameInXHTMLNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('P', $document->getElementById('m3p-mi')->firstChild->firstChild->nodeName);
        self::assertSame(Namespaces::HTML, $document->getElementById('m3p-mi')->firstChild->firstChild->namespaceURI);
    }

    public function testHtmlPInMrowTerminatesTheMathMrowPMIChildrenOfDiv(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(3, $document->getElementById('d3p-mrow')->childNodes->length);
    }

    public function testHtmlPInMrowTerminatesTheMathMrowChildOfMath(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(1, $document->getElementById('d3p-mrow')->firstChild->childNodes->length);
    }

    public function testHtmlPInMrowTerminatesTheMathMrowEmpty(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(0, $document->getElementById('d3p-mrow')->firstChild->firstChild->childNodes->length);
    }

    public function testHtmlPInMrowTerminatesTheMathMathMrowPMIChildrenOfDiv(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('math', $document->getElementById('d3p-mrow')->childNodes[0]->nodeName);
        self::assertSame('P', $document->getElementById('d3p-mrow')->childNodes[1]->nodeName);
        self::assertSame('MI', $document->getElementById('d3p-mrow')->childNodes[2]->nodeName);
    }

    public function testUndefinedelementInMtextProducesUNDEFINEDELEMENTNodenameInXHTMLNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('UNDEFINEDELEMENT', $document->getElementById('m4')->firstChild->firstChild->nodeName);
        self::assertSame(Namespaces::HTML, $document->getElementById('m4')->firstChild->firstChild->namespaceURI);
    }

    public function testMiInMtextProducesMINodenameInXHTMLNamespace(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('MI', $document->getElementById('m5')->firstChild->firstChild->nodeName);
        self::assertSame(Namespaces::HTML, $document->getElementById('m5')->firstChild->firstChild->namespaceURI);
    }

    public function testPInAnnotationXmlMovesToBeChildOfDIV(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('DIV', $document->getElementById('p6default')->parentNode->nodeName);
    }

    public function testPInAnnotationXmlEncodingTextHtmlStaysAsChildOfAnnotationXml(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('annotation-xml', $document->getElementById('p6texthtml')->parentNode->nodeName);
    }

    public function testPInAnnotationXmlEncodingUppercaseTextHtmlStaysAsChildOfAnnotationXml(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('annotation-xml', $document->getElementById('p6uctexthtml')->parentNode->nodeName);
    }

    public function testPInAnnotationXmlEncodingApplicationXhtmlXmlStaysAsChildOfAnnotationXml(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('annotation-xml', $document->getElementById('p6applicationxhtmlxml')->parentNode->nodeName);
    }

    public function testPInAnnotationXmlEncodingFooMovesToBeChildOfDIV(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('DIV', $document->getElementById('p6foo')->parentNode->nodeName);
    }

    public static function getDocumentName(): string
    {
        return 'math-parse03.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
