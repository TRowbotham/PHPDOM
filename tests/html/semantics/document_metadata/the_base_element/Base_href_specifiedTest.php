<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Environment;
use Rowbot\DOM\Tests\common\GetHostInfoSubTrait;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/document-metadata/the-base-element/base_href_specified.html
 */
class Base_href_specifiedTest extends TestCase
{
    use GetHostInfoSubTrait;
    use WindowTrait;

    private static $otherOrigin;
    private static $testElement;
    private static $baseElement;

    public function testHrefAttrOfBaseIsSpecified(): void
    {
        self::assertSame(self::$otherOrigin . '/', self::$baseElement->href);
    }

    public function testSrcAttrOfTheImgElementMustBeRelativeToTheHrefAttrOfTheBaseElement(): void
    {
        self::assertSame(self::$otherOrigin . '/test.ico', self::$testElement->src);
    }

    public static function setUpBeforeClass(): void
    {
        self::$otherOrigin = self::get_host_info()['HTTP_REMOTE_ORIGIN'];
        self::$testElement = self::getWindow()->document->getElementById('test');
        self::$baseElement = self::getWindow()->document->getElementById('base');
        self::$baseElement->setAttribute('href', self::$otherOrigin);
    }

    public static function tearDownAfterClass(): void
    {
        self::$otherOrigin = null;
        self::$testElement = null;
        self::$baseElement = null;
    }

    public static function getDocumentName(): string
    {
        return 'base_href_specified.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
    }

    public static function getBuilder(): DocumentBuilder
    {
        return DocumentBuilder::create()->setContentType('text/html')->setDocumentUrl('http://localhost');
    }
}
