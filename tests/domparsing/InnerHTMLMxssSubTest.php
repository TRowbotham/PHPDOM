<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function hexdec;
use function mb_chr;
use function rawurlencode;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/innerhtml-mxss.sub.html
 */
class InnerHTMLMxssSubTest extends TestCase
{
    use WindowTrait;

    private const WHITESPACES = [
        ["1680"],
        ["2000"],
        ["2001"],
        ["2002"],
        ["2003"],
        ["2004"],
        ["2005"],
        ["2006"],
        ["2007"],
        ["2008"],
        ["2009"],
        ["200a"],
        ["2028"],
        ["205f"],
        ["3000"],
    ];

    /**
     * @requires PHP > 7.1
     * @dataProvider whitespaceProvider
     */
    public function testInnerHTML(string $whitespace): void
    {
        $document = self::getWindow()->document;
        // $container = $document->querySelector('a')->parentNode;
        $container = $document->getElementsByTagName('a')[0]->parentNode;
        $entity = "&#x{$whitespace};";
        $character = mb_chr(hexdec($whitespace), 'utf-8');
        $url = rawurlencode($character);
        $container->innerHTML = "<a href=\"{$entity}javascript:alert(1)\">Link</a>";

        // $a = $document->querySelector('a');
        $a = $document->getElementsByTagName('a')[0];

        self::assertSame(
            "<a href=\"{$character}javascript:alert(1)\">Link</a>",
            $container->innerHTML
        );
        // self::assertSame(
        //     "http://{{host}}:{{ports[http][0]}}/domparsing/{$url}javascript:alert(1)",
        //     $a->href
        // );

        $a->parentNode->innerHTML .= 'foo';
        // $a = $document->querySelector('a');
        $a = $document->getElementsByTagName('a')[0];

        self::assertSame(
            "<a href=\"{$character}javascript:alert(1)\">Link</a>foo",
            $container->innerHTML
        );
        // self::assertSame(
        //     "http://{{host}}:{{ports[http][0]}}/domparsing/{$url}javascript:alert(1)",
        //     $a->href
        // );
    }

    public function whitespaceProvider(): array
    {
        return self::WHITESPACES;
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'innerhtml-mxss.sub.html';
    }
}
