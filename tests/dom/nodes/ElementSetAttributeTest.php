<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-setAttribute.html
 */
class ElementSetAttributeTest extends TestCase
{
    public function testSetAttributeShouldChangeTheFirstAttributeIrrespectiveOfNamespace(): void
    {
        $document = new HTMLDocument();
        $el = $document->createElement('p');
        $el->setAttributeNS('foo', 'x', 'first');
        $el->setAttributeNS('foo2', 'x', 'second');

        $el->setAttribute('x', 'changed');

        $this->assertSame(2, $el->attributes->length);
        $this->assertSame('changed', $el->getAttribute('x'));
        $this->assertSame('changed', $el->getAttributeNS('foo', 'x'));
        $this->assertSame('second', $el->getAttributeNS('foo2', 'x'));
    }

    public function testSetAttributeShouldLowercaseBeforeSetting(): void
    {
        $document = new HTMLDocument();
        $el = $document->createElement('p');
        $el->setAttribute('FOO', 'bar');

        $this->assertSame('bar', $el->getAttribute('foo'));
        $this->assertSame('bar', $el->getAttribute('FOO'));
        $this->assertSame('bar', $el->getAttributeNS('', 'foo'));
        $this->assertNull($el->getAttributeNS('', 'FOO'));
    }
}
