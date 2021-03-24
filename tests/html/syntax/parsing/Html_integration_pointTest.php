<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/html-integration-point.html
 */
class Html_integration_pointTest extends TestCase
{
    use WindowTrait;

    /**
     * @dataProvider idProvider
     */
    public function testIntegrationPoint(string $id): void
    {
        $document = self::getWindow()->document;
        // $point = $document->querySelector('#' . $id);
        $point = $document->getElementById($id);

        self::assertNotSame(Namespaces::HTML, $point->namespaceURI);
        $rawTextElement = $point->firstChild;
        self::assertSame(Namespaces::HTML, $rawTextElement->namespaceURI);
        self::assertSame('&lt;', substr($rawTextElement->textContent, 0, 4));
    }

    public function idProvider(): array
    {
        return [
            ['point-1'], // MathML annotation-xml with encoding=text/html should be an HTML integration point
            ['point-2'], // MathML annotation-xml with encoding=application/xhtml+xml should be an HTML integration point
            ['point-3'], // SVG foreignObject should be an HTML integration point
            ['point-4'], // SVG desc should be an HTML integration point
            ['point-5'], // SVG title should be an HTML integration point
        ];
    }

    public static function getDocumentName(): string
    {
        return 'html-integration-point.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
