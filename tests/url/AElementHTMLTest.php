<?php

namespace Rowbot\DOM\Tests\url;

use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\Tests\url\resources\AElementTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/a-element-origin.html
 */
class AElementHTMLTest extends TestCase
{
    use AElementTrait;

    public static function getDocumentName(): string
    {
        return 'a-element.html';
    }
}
