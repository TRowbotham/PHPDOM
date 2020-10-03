<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

class NodeContainsTest extends TestCase
{
    use Common;
    use DocumentGetter;

    public function rangeTestNodesProvider()
    {
        $document = $this->getHTMLDocument();
        self::setupRangeTests($document);

        return $this->getTestNodes();
    }

    /**
     * @dataProvider rangeTestNodesProvider
     */
    public function testContains($referenceName, $otherName)
    {
        $document = $this->getHTMLDocument();
        $reference = $this->eval($referenceName, $document);
        $this->assertFalse($reference->contains(null));

        $other = $this->eval($otherName, $document);
        $ancestor = $other;

        while ($ancestor && $ancestor !== $reference) {
            $ancestor = $ancestor->parentNode;
        }

        $this->assertEquals(
            $ancestor === $reference,
            $reference->contains($other)
        );
    }
}
