<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url\resources;

use Generator;

use function array_key_exists;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/resources/a-element-origin.js
 */
trait AElementOriginTrait
{
    use DataProviderTrait;

    /**
     * @dataProvider urlOriginTestProvider
     */
    public function testUrlOrigin(array $expected): void
    {
        $url = $this->bURL($expected['input'], $expected['base']);
        self::assertSame($expected['origin'], $url->origin);
    }

    public function urlOriginTestProvider(): Generator
    {
        foreach ($this->decodeUrlTestData() as $data) {
            if (array_key_exists('origin', $data)) {
                yield [$data];
            }
        }
    }
}
