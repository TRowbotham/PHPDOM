<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url;

use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\Tests\url\resources\AElementOriginTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/a-element-origin.html
 */
class AElementHTMLOriginTest extends TestCase
{
    use AElementOriginTrait;

    public static function getDocumentName(): string
    {
        return 'a-element-origin.html';
    }
}
