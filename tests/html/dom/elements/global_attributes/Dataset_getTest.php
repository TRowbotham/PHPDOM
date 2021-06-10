<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\DOMStringMap;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/dataset-get.html
 */
class Dataset_getTest extends TestCase
{
    /**
     * @dataProvider attributesProvider
     */
    public function testGet(string $attr, string $expected): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');
        $d->setAttribute($attr, 'value');

        self::assertSame('value', $d->dataset[$expected]);
    }

    /**
     * @dataProvider nonMatchingAttributesProvider
     */
    public function testMatchesNothingInDataset(string $attr): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');
        $d->setAttribute($attr, 'value');

        self::assertInstanceOf(DOMStringMap::class, $d->dataset);
        self::assertFalse($d->dataset->getIterator()->valid());
    }

    public function attributesProvider(): array
    {
        return [
            ['data-foo', 'foo'],
            ['data-foo-bar', 'fooBar'],
            ['data--', '-'],
            ['data--foo', 'Foo'],
            ['data---foo', '-Foo'],
            ['data-Foo', 'foo'],
            ['data-', ''],
            ["data-\u{00E0}", "\u{00E0}"],
            ['data-to-string', 'toString'],
        ];
    }

    public function nonMatchingAttributesProvider(): array
    {
        return [['dataFoo']];
    }
}
