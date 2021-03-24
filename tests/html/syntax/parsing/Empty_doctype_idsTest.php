<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/empty-doctype-ids.html
 */
class Empty_doctype_idsTest extends TestCase
{
    use WindowTrait;

    public function testDoctypeWithEmptyIdsShouldTriggerStandardsMode(): void
    {
        self::assertSame('CSS1Compat', self::getWindow()->document->compatMode);
    }

    public static function getDocumentName(): string
    {
        return 'empty-doctype-ids.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
