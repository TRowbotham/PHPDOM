<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-siblingElement-null.html
 */
class ElementSiblingElementNullTest extends NodeTestCase
{
    use WindowTrait;

    public function testSiblingElements(): void
    {
        $fec = self::getWindow()->document->getElementById('first_element_child');
        $this->assertNull($fec->previousElementSibling);
        $this->assertNull($fec->nextElementSibling);
    }

    public static function getDocumentName(): string
    {
        return 'Element-siblingElement-null.html';
    }
}
