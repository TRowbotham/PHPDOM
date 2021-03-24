<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/meta-inhead-insertion-mode.html
 */
class Meta_inhead_insertion_modeTest extends TestCase
{
    use WindowTrait;

    public function testEncodingSpecifiedinTheCharsetAttributeShouldHavePrecendenceOverContentAttribute(): void
    {
        self::assertSame('ISO-8859-15', self::getWindow()->document->characterSet);
    }

    public static function getDocumentName(): string
    {
        return 'meta-inhead-insertion-mode.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
