<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/Element.getElementsByTagName-foreign-01.html
 */
class Element_getElementsByTagName_foreign_01Test extends TestCase
{
    use WindowTrait;

    public function testUpperCaseFont(): void
    {
        $document = self::getWindow()->document;
        $wrapper = $document->getElementById('test');
        self::assertSame(1, $wrapper->getElementsByTagName('FONT')->length);
        self::assertSame(Namespaces::HTML, $wrapper->getElementsByTagName('FONT')[0]->namespaceURI);
    }

    public function testLowerCaseFont(): void
    {
        $document = self::getWindow()->document;
        $wrapper = $document->getElementById('test');
        self::assertSame(2, $wrapper->getElementsByTagName('font')->length);
        self::assertSame(Namespaces::HTML, $wrapper->getElementsByTagName('font')[0]->namespaceURI);
        self::assertSame(Namespaces::SVG, $wrapper->getElementsByTagName('font')[1]->namespaceURI);
    }

    public static function getDocumentName(): string
    {
        return 'Element.getElementsByTagName-foreign-01.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
