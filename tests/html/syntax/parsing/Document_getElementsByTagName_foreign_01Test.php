<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function strtolower;
use function strtoupper;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/Document.getElementsByTagName-foreign-01.html
 */
class Document_getElementsByTagName_foreign_01Test extends TestCase
{
    use WindowTrait;

    /**
     * @dataProvider elementNameProvider
     */
    public function testGetElementsByTagName(string $el): void
    {
        $document = self::getWindow()->document;
        self::assertSame(2, $document->getElementsByTagName($el)->length);
        self::assertSame(0, $document->getElementsByTagName(strtoupper($el))->length);
        self::assertSame(0, $document->getElementsByTagName(strtolower($el))->length);
        self::assertSame(2, $document->getElementsByTagNameNS(Namespaces::SVG, $el)->length);
        self::assertSame(0, $document->getElementsByTagNameNS(Namespaces::SVG, strtoupper($el))->length);
        self::assertSame(0, $document->getElementsByTagNameNS(Namespaces::SVG, strtolower($el))->length);
    }

    public function elementNameProvider(): array
    {
        return [
            ["altGlyph"],
            ["altGlyphDef"],
            ["altGlyphItem"],
            ["animateColor"],
            ["animateMotion"],
            ["animateTransform"],
            ["clipPath"],
            ["feBlend"],
            ["feColorMatrix"],
            ["feComponentTransfer"],
            ["feComposite"],
            ["feConvolveMatrix"],
            ["feDiffuseLighting"],
            ["feDisplacementMap"],
            ["feDistantLight"],
            ["feFlood"],
            ["feFuncA"],
            ["feFuncB"],
            ["feFuncG"],
            ["feFuncR"],
            ["feGaussianBlur"],
            ["feImage"],
            ["feMerge"],
            ["feMergeNode"],
            ["feMorphology"],
            ["feOffset"],
            ["fePointLight"],
            ["feSpecularLighting"],
            ["feSpotLight"],
            ["feTile"],
            ["feTurbulence"],
            ["foreignObject"],
            ["glyphRef"],
            ["linearGradient"],
            ["radialGradient"],
            ["textPath"],
        ];
    }

    public static function getDocumentName(): string
    {
        return 'Document.getElementsByTagName-foreign-01.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
