<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

abstract class RangeTestCase extends TestCase
{
    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
