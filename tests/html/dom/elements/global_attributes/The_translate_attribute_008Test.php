<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/the-translate-attribute-008.html
 */
class The_translate_attribute_008Test extends TestCase
{
    use WindowTrait;

    public function testTranslateAttribute(): void
    {
        self::assertTrue(self::getWindow()->document->getElementById('box')->translate);
    }

    public static function getDocumentName(): string
    {
        return 'the-translate-attribute-008.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
