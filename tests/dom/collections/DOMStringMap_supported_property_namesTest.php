<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/collections/domstringmap-supported-property-names.html
 */
class DOMStringMap_support_property_namesTest extends TestCase
{
    use WindowTrait;

    public function testEmptyDataAttribute(): void
    {
        // $element = self::getWindow()->document->querySelector('#edge1');
        $element = self::getWindow()->document->getElementById('edge1');

        self::assertSame([''], iterator_to_array($element->dataset));
    }

    public function testDataAttributeWithTrailingHyphen(): void
    {
        // $element = self::getWindow()->document->querySelector('#edge2');
        $element = self::getWindow()->document->getElementById('edge2');

        self::assertSame(['id-'], iterator_to_array($element->dataset));
    }

    public function testDataAttributeWithMultipleAttributes(): void
    {
        // $element = self::getWindow()->document->querySelector('#user');
        $element = self::getWindow()->document->getElementById('user');

        self::assertSame(['id', 'user', 'dateOfBirth'], iterator_to_array($element->dataset));
    }

    public function testDatasetSetInScript(): void
    {
        // $element = self::getWindow()->document->querySelector('#user2');
        $element = self::getWindow()->document->getElementById('user2');
        $element->dataset->middleName = 'mark';

        self::assertSame(['uniqueId', 'middleName'], iterator_to_array($element->dataset));
    }

    public function testAttributeSetOnElementInScript(): void
    {
        // $element = self::getWindow()->document->querySelector('#user3');
        $element = self::getWindow()->document->getElementById('user3');
        $element->setAttribute('data-age', '30');

        self::assertSame(['uniqueId', 'age'], iterator_to_array($element->dataset));
    }

    public static function getDocumentName(): string
    {
        return 'domstringmap-supported-property-names.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
