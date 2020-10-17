<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-nextElementSibling.html
 */
class ElementNextElementSiblingTest extends NodeTestCase
{
    use WindowTrait;

    public function testPreviousElementSibling(): void
    {
        $document = self::getWindow()->document;
        $parentEl = $document->getElementById('parentEl');
        $fec = $document->getElementById('first_element_child');
        $nes = $fec->nextElementSibling;

        $this->assertNotNull($nes);
        $this->assertSame(1, $nes->nodeType);
        $this->assertSame('last_element_child', $nes->getAttribute('id'));
    }

    public static function getDocumentName(): string
    {
        return 'Element-nextElementSibling.html';
    }
}
