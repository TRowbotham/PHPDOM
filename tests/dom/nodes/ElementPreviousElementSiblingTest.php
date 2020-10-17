<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-previousElementSibling.html
 */
class ElementPreviousElementSiblingTest extends NodeTestCase
{
    use WindowTrait;

    public function testPreviousElementSibling(): void
    {
        $document = self::getWindow()->document;
        $parentEl = $document->getElementById('parentEl');
        $lec = $document->getElementById('last_element_child');
        $pes = $lec->previousElementSibling;

        $this->assertNotNull($pes);
        $this->assertSame(1, $pes->nodeType);
        $this->assertSame('middle_element_child', $pes->getAttribute('id'));
    }

    public static function getDocumentName(): string
    {
        return 'Element-previousElementSibling.html';
    }
}
