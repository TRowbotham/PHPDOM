<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/Document.getElementsByTagName-foreign-02.html
 */
class Document_getElementsByTagName_foreign_02Test extends TestCase
{
    use WindowTrait;

    public function testUpperCaseFont(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(1, $document->getElementsByTagName('FONT')->length);
        self::assertSame(Namespaces::HTML, $document->getElementsByTagName('FONT')[0]->namespaceURI);
    }

    public function testLowerCaseFont(): void
    {
        $document = self::getWindow()->document;
        self::assertSame(2, $document->getElementsByTagName('font')->length);
        self::assertSame(Namespaces::HTML, $document->getElementsByTagName('font')[0]->namespaceURI);
        self::assertSame(Namespaces::SVG, $document->getElementsByTagName('font')[1]->namespaceURI);
    }

    public static function getDocumentName(): string
    {
        return 'Document.getElementsByTagName-foreign-02.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
