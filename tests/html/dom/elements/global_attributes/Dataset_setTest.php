<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Exception\SyntaxError;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/dataset-set.html
 */
class Dataset_setTest extends TestCase
{
    /**
     * @dataProvider attributesProvider
     */
    public function testSet(string $prop, string $expected): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');
        $d->dataset[$prop] = 'value';

        self::assertSame('value', $d->getAttribute($expected));
    }

    /**
     * @dataProvider invalidAttributesProvider
     */
    public function testSetThrows(string $prop, string $exception): void
    {
        $this->expectException($exception);
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');
        $d->dataset[$prop] = 'value';
    }

    public function attributesProvider(): array
    {
        return [
            ['foo', 'data-foo'],
            ['fooBar', 'data-foo-bar'],
            ['-', 'data--'],
            ['Foo', 'data--foo'],
            ['-Foo', 'data---foo'],
            ['', 'data-'],
            ["\u{00E0}", "data-\u{00E0}"],
            ["\u{0BC6}foo", "data-\u{0BC6}foo"],
        ];
    }

    public function invalidAttributesProvider(): array
    {
        return [
            ['-foo', SyntaxError::class],
            ["foo\x20", InvalidCharacterError::class],
            ["\u{037E}foo", InvalidCharacterError::class],
        ];
    }
}
