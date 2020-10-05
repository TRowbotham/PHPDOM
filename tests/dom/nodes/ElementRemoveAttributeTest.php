<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-removeAttribute.html
 */
class ElementRemoveAttributeTest extends TestCase
{
    public function testRemoveAttribute1(): void
    {
        $document = new HTMLDocument();
        $el = $document->createElement('p');
        $el->setAttribute('x', 'first');
        $el->setAttributeNS('foo', 'x', 'second');

        $this->assertSame(2, $el->attributes->length);
        $this->assertSame('first', $el->getAttribute('x'));
        $this->assertSame('first', $el->getAttributeNS(null, 'x'));
        $this->assertSame('second', $el->getAttributeNS('foo', 'x'));

        // removeAttribute removes the first attribute with name "x" that
        // we set on the element, irrespective of namespace.
        $el->removeAttribute('x');

        // The only attribute remaining should be the second one.
        $this->assertSame('second', $el->getAttribute('x'));
        $this->assertNull($el->getAttributeNS(null, 'x'));
        $this->assertSame('second', $el->getAttributeNS('foo', 'x'));
        $this->assertSame(1, $el->attributes->length);
    }

    public function testRemoveAttribute2(): void
    {
        $document = new HTMLDocument();
        $el = $document->createElement('p');
        $el->setAttributeNS('foo', 'x', 'first');
        $el->setAttributeNS('foo2', 'x', 'second');

        $this->assertSame(2, $el->attributes->length);
        $this->assertSame('first', $el->getAttribute('x'));
        $this->assertSame('first', $el->getAttributeNS('foo', 'x'));
        $this->assertSame('second', $el->getAttributeNS('foo2', 'x'));

        // removeAttribute removes the first attribute with name "x" that
        // we set on the element, irrespective of namespace.
        $el->removeAttribute('x');

        // The only attribute remaining should be the second one.
        $this->assertSame('second', $el->getAttribute('x'));
        $this->assertNull($el->getAttributeNS('foo', 'x'));
        $this->assertSame('second', $el->getAttributeNS('foo2', 'x'));
        $this->assertSame(1, $el->attributes->length);
    }
}
