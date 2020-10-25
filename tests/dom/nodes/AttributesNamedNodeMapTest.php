<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use ReflectionObject;
use Rowbot\DOM\HTMLDocument;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/attributes-namednodemap.html
 */
class AttributesNamedNodeMapTest extends NodeTestCase
{
    public function testAnAttributeSetBySetAttributeShouldBeAccessibleAsAFieldOnTheAttributesFieldOfAnElement(): void
    {
        $document = new HTMLDocument();
        $element = $document->createElement('div');
        $element->setAttribute('x', 'first');

        self::assertSame(1, $element->attributes->length);
        self::assertSame('first', $element->attributes->x->value);
    }

    public function testSetNamedItemAndRemoveNamedItemOnAttributesShouldAddAndRemoveFieldsFromAttributes(): void
    {
        $document = new HTMLDocument();
        $element = $document->createElement('div');
        $map = $element->attributes;

        self::assertSame(0, $map->length);

        $attr1 = $document->createAttribute('attr1');
        $map->setNamedItem($attr1);
        self::assertSame($attr1, $map->attr1);
        self::assertSame(1, $map->length);

        $attr2 = $document->createAttribute('attr2');
        $map->setNamedItem($attr2);
        self::assertSame($attr2, $map->attr2);
        self::assertSame(2, $map->length);

        $rm1 = $map->removeNamedItem('attr1');
        self::assertSame($attr1, $rm1);
        self::assertSame(1, $map->length);

        $rm2 = $map->removeNamedItem('attr2');
        self::assertSame($attr2, $rm2);
        self::assertSame(0, $map->length);
    }

    public function testSetNamedItemAndRemoveNamedItemOnAttributesShouldNotInterfereWithExistingMethodNames(): void
    {
        $document = new HTMLDocument();
        $element = $document->createElement('div');
        $map = $element->attributes;

        $fooAttribute = $document->createAttribute('foo');
        $map->setNamedItem($fooAttribute);

        $itemAttribute = $document->createAttribute('item');
        $map->setNamedItem($itemAttribute);
        $reflection = new ReflectionObject($map);

        self::assertSame($fooAttribute, $map->foo);
        self::assertTrue($reflection->hasMethod('item'));

        $map->removeNamedItem('item');
        self::assertTrue($reflection->hasMethod('item'));
    }

    public function testAnAttributeWithANullNamespaceShouldBeAccessibleAsAFieldOnTheAttributesFieldOfAnElement(): void
    {
        $document = new HTMLDocument();
        $element = $document->createElement('div');
        $element->setAttributeNS(null, 'x', 'first');

        self::assertSame(1, $element->attributes->length);
        self::assertSame('first', $element->attributes->x->value);
    }

    public function testAnAttributeWithASetNamespaceShouldBeAccessibleAsAFieldOnTheAttributesFieldOfAnElement(): void
    {
        $document = new HTMLDocument();
        $element = $document->createElement('div');
        $element->setAttributeNS('foo', 'x', 'first');

        self::assertSame(1, $element->attributes->length);
        self::assertSame('first', $element->attributes->x->value);
    }

    public function testSettingAnAttributeShouldNotOverwriteTheMethodsOfAnNamedNodeMapObject(): void
    {
        $document = new HTMLDocument();
        $element = $document->createElement('div');
        $element->setAttributeNS('foo', 'setNamedItem', 'first');

        self::assertSame(1, $element->attributes->length);
        self::assertTrue((new ReflectionObject($element->attributes))->hasMethod('setNamedItem'));
    }

    public function testSettingAnAttributeShouldNotOverwriteTheLengthPropertyAnNamedNodeMapObject(): void
    {
        $document = new HTMLDocument();
        $element = $document->createElement('div');
        $element->setAttributeNS('foo', 'length', 'first');

        self::assertSame(1, $element->attributes->length);
    }
}
