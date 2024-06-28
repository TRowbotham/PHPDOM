<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\Tests\url\resources\AElementTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/a-element-origin.html
 */
class AElementHTMLTest extends TestCase
{
    use AElementTrait;

    public function testEmbeddedNewLineIsStripped(): void
    {
        $link = self::getWindow()->document->getElementById('multline-entity');
        self::assertSame("data:text/plain;charset=utf-8,first%20linesecond%20line", $link->href);
    }

    public static function getDocumentName(): string
    {
        return 'a-element.html';
    }
}
