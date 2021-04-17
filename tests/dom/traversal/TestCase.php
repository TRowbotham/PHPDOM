<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\traversal;

use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase as DOMTestCase;

use const DIRECTORY_SEPARATOR;

abstract class TestCase extends DOMTestCase
{
    public function assertNode(array $expected, Node $actual)
    {
        $this->assertInstanceOf($expected['type'], $actual);

        if (isset($expected['id'])) {
            $this->assertSame($expected['id'], $actual->id);
        }

        if (isset($expected['nodeValue'])) {
            $this->assertSame($expected['nodeValue'], $actual->nodeValue);
        }
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
