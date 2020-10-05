<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-removeAttributeNS.html
 */
class ElementRemoveAttributeNSTest extends TestCase
{
    use Attributes;

    public function testRemoveAttributeNS(): void
    {
        $document = new HTMLDocument();
        $el = $document->createElement('foo');
        $el->setAttributeNS(Namespaces::XML, 'a:bb', 'pass');
        $this->attr_is($el->attributes[0], 'pass', 'bb', Namespaces::XML, 'a', 'a:bb');
        $el->removeAttributeNS(Namespaces::XML, 'a:bb');
        $this->assertSame(1, $el->attributes->length);
        $this->attr_is($el->attributes[0], 'pass', 'bb', Namespaces::XML, 'a', 'a:bb');
    }
}
