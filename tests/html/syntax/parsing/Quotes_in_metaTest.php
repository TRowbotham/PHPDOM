<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/quotes-in-meta.html
 */
class Quotes_in_metaTest extends TestCase
{
    use WindowTrait;

    public function testCharsetEqualsIsIgnoredIfFollowedByUnmatchedQuote(): void
    {
        self::assertSame('windows-1250', self::getWindow()->document->characterSet);
    }

    public static function getDocumentName(): string
    {
        return 'quotes-in-meta.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
