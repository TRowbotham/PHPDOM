<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\dom\elements\global_attributes;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Tests\TestCase;

use function count;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/dom/elements/global-attributes/dataset-enumeration.html
 */
class Dataset_enumerationTest extends TestCase
{
    /**
     * @dataProvider attributesProvider
     */
    public function testEnumeration(array $array, int $expectedCount): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $d = $document->createElement('div');

        for ($i = 0, $length = count($array); $i < $length; ++$i) {
            $d->setAttribute($array[$i], 'value');
        }

        $count = 0;

        foreach ($d->dataset as $item) {
            ++$count;
        }

        self::assertSame($expectedCount, $count);
    }

    public function attributesProvider(): array
    {
        return [
            [['data-foo', 'data-bar', 'data-baz'], 3],
            [['data-foo', 'data-bar', 'dataFoo'], 2],
        ];
    }
}
