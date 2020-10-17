<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-lastElementChild.html
 */
class ElementLastElementChildTest extends NodeTestCase
{
    use WindowTrait;

    public function testPreviousElementSibling(): void
    {
        $document = self::getWindow()->document;
        $parentEl = $document->getElementById('parentEl');
        $lec = $parentEl->lastElementChild;

        $this->assertNotNull($lec);
        $this->assertSame(1, $lec->nodeType);
        $this->assertSame('last_element_child', $lec->getAttribute('id'));
    }

    public static function getDocumentName(): string
    {
        return 'Element-lastElementChild.html';
    }
}
