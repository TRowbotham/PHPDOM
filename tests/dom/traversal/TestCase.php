<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use ReflectionObject;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\Tests\TestCase as DOMTestCase;

abstract class TestCase extends DOMTestCase
{
    public function assertNode(array $expected, Node $actual)
    {
        $this->assertInstanceOf($expected['type'], $actual);

        if (isset($expected['id'])) {
            $this->assertEquals($expected['id'], $actual->id);
        }

        if (isset($expected['nodeValue'])) {
            $this->assertEquals($expected['nodeValue'], $actual->nodeValue);
        }
    }

    /**
     * @param \Rowbot\DOM\NodeIterator|\Rowbot\DOM\TreeWalker $iter
     * @param \Rowbot\DOM\NodeFilter|Closure|null             $filter
     */
    public function assertSameFilter($iter, $filter): void
    {
        if ($filter === null) {
            $this->assertNull($iter->filter);

            return;
        }

        // Since we internally convert filters to NodeFilter, we look at the underlying
        // filter property. This test only uses Closures, so no need to do anything fancier.
        $this->assertInstanceOf(NodeFilter::class, $iter->filter);

        $reflection = new ReflectionObject($iter->filter);
        $prop = $reflection->getProperty('filter');
        $prop->setAccessible(true);
        $this->assertSame($filter, $prop->getValue($iter->filter));
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
