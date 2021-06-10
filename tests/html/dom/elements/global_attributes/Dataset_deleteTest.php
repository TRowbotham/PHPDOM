<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/dataset-delete.html
 */
class Dataset_deleteTest extends TestCase
{
    /**
     * @dataProvider datasetProvider
     */
    public function testDelete(string $attr, string $prop): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');
        $d->setAttribute($attr, 'value');
        unset($d->dataset[$prop]);

        self::assertFalse($d->hasAttribute($attr));
        self::assertNotSame('value', $d->getAttribute($attr));
    }

    /**
     * @dataProvider datasetNoAddProvider
     */
    public function testDeleteNoAdd(string $prop): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');
        $length = $d->attributes->length;
        unset($d->dataset[$prop]);

        self::assertSame($length, $d->attributes->length);
    }

    public function test1(): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');
        $d->setAttribute('data--foo', 'value');
        self::assertNull($d->dataset['-foo']);
        self::assertFalse(isset($d->dataset['-foo']));
        unset($d->dataset['-foo']);
        self::assertTrue($d->hasAttribute('data--foo'));
        self::assertSame('value', $d->getAttribute('data--foo'));
    }

    public function datasetProvider(): array
    {
        return [
            ['data-foo', 'foo'],
            ['data-foo-bar', 'fooBar'],
            ['data--', '-'],
            ['data--foo', 'Foo'],
            ['data---foo', '-Foo', '-Foo'],
            ['data-', ''],
            ["data-\u{00E0}", "\u{00E0}"],
        ];
    }

    public function datasetNoAddProvider(): array
    {
        return [['foo']];
    }
}
