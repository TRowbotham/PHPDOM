<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Tests\dom\nodes\Attributes;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/custom-attrs.html
 */
class Custom_attrsTest extends TestCase
{
    use Attributes;

    public function testSettingDatasetPropertyShouldNotInterfereWithNamespacedAttributesWithTheSameName(): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $div = $document->createElement('div');
        $div->setAttributeNS('foo', 'data-my-custom-attr', 'first');
        $div->setAttributeNS('bar', 'data-my-custom-attr', 'second');
        $div->dataset->myCustomAttr = 'third';

        self::assertSame(3, $div->attributes->length);
        $this->attributes_are($div, [
            ["data-my-custom-attr", "first", "foo"],
            ["data-my-custom-attr", "second", "bar"],
            ["data-my-custom-attr", "third", null],
        ]);
    }
}
