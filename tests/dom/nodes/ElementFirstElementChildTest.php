<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-firstElementChild.html
 */
class ElementFirstElementChildTest extends NodeTestCase
{
    use WindowTrait;

    public function testPreviousElementSibling(): void
    {
        $document = self::getWindow()->document;
        $parentEl = $document->getElementById('parentEl');
        $fec = $parentEl->firstElementChild;

        $this->assertNotNull($fec);
        $this->assertSame(1, $fec->nodeType);
        $this->assertSame('first_element_child', $fec->getAttribute('id'));
    }

    public static function getDocumentName(): string
    {
        return 'Element-firstElementChild.html';
    }
}
