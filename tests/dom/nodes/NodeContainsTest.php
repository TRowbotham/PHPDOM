<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Node-contains.html
 */
class NodeContainsTest extends NodeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider rangeTestNodesProvider
     */
    public function testContains($referenceName, $otherName)
    {
        $window = self::getWindow();
        $reference = $window->eval($referenceName);
        $this->assertFalse($reference->contains(null));

        $other = $window->eval($otherName);
        $ancestor = $other;

        while ($ancestor && $ancestor !== $reference) {
            $ancestor = $ancestor->parentNode;
        }

        $this->assertSame(
            $ancestor === $reference,
            $reference->contains($other)
        );
    }

    public function rangeTestNodesProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        foreach ($window->testNodes as $referenceName) {
            foreach ($window->testNodes as $otherName) {
                yield [$referenceName, $otherName];
            }
        }
    }

    public static function getDocumentName(): string
    {
        return 'Node-contains.html';
    }
}
