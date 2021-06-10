<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\document_metadata\the_base_element;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/document-metadata/the-base-element/base_multiple.html
 */
class Base_multipleTest extends TestCase
{
    use WindowTrait;

    public function testMultipleBaseElements(): void
    {
        $document = self::getWindow()->document;
        self::assertSame('http://example.com/example2.html', $document->getElementById('a1')->href);
    }

    public static function getDocumentName(): string
    {
        return 'example.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }

    public static function getBuilder(): DocumentBuilder
    {
        return DocumentBuilder::create()->setDocumentUrl('http://example.com/')->setContentType('text/html');
    }
}
