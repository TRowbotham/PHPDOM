<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\TestCase;

abstract class NodeTestCase extends TestCase
{
    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
