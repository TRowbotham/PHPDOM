<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/data_unicode_attr.html
 */
class Data_unicode_attrTest extends TestCase
{
    use WindowTrait;

    public function testDatasetSBCS(): void
    {
        $d1 = self::getWindow()->document->getElementById('d1');
        self::assertSame('laser 2', $d1->dataset->weapons);
    }

    public function testDatasetUnicode(): void
    {
        $d1 = self::getWindow()->document->getElementById('d1');
        self::assertSame('中文', $d1->dataset->中文属性);
    }

    public static function getDocumentName(): string
    {
        return 'data_unicode_attr.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
