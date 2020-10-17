<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-firstElementChild-namespace.html
 */
class ElementFirstElementChildNamespaceTest extends NodeTestCase
{
    use WindowTrait;

    public function testPreviousElementSibling(): void
    {
        $document = self::getWindow()->document;
        $parentEl = $document->getElementById('parentEl');
        $el = $document->createElementNS("http://ns.example.org/pickle", "pickle:dill");
        $el->setAttribute('id', 'first_element_child');
        $parentEl->appendChild($el);
        $fec = $parentEl->firstElementChild;

        $this->assertNotNull($fec);
        $this->assertSame(1, $fec->nodeType);
        $this->assertSame('first_element_child', $fec->getAttribute('id'));
        $this->assertSame('dill', $fec->localName);
    }

    public static function getDocumentName(): string
    {
        return 'Element-firstElementChild-namespace.html';
    }
}
