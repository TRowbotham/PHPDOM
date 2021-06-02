<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\document_metadata\the_base_element;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function strpos;
use function strrpos;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/document-metadata/the-base-element/base_href_unspecified.html
 */
class Base_href_unspecifiedTest extends TestCase
{
    use WindowTrait;

    private const LOCATION_HREF = 'https://example.com/';

    public function testValueOfHrefAttributeMustBeDocumentsAddressIfEmpty(): void
    {
        $document = self::getWindow()->document;
        $baseElement = $document->getElementById("base");
        self::assertSame(self::LOCATION_HREF, $baseElement->href);
    }

    public function testSrcAttributeOfImageElementMustBeRelativeToDocumentsAddress(): void
    {
        $document = self::getWindow()->document;
        $testElement = $document->getElementById("test");
        $baseElement = $document->getElementById("base");
        $exp = substr($testElement->src, 0, strrpos($testElement->src, "/images/blue-100x100.png") + 1);
        self::assertNotFalse(strpos($baseElement->href, $exp));
    }

    public static function getDocumentName(): string
    {
        return 'base_href_unspecified.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
    }

    public static function getBuilder(): DocumentBuilder
    {
        return DocumentBuilder::create()->setContentType('text/html')->setDocumentUrl(self::LOCATION_HREF);
    }
}
