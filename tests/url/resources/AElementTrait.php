<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url\resources;

use Generator;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/resources/a-element.js
 */
trait AElementTrait
{
    use DataProviderTrait;

    /**
     * @dataProvider urlTestDataProvider
     */
    public function testUrl(array $expected): void
    {
        $url = $this->bURL($expected['input'], $expected['base']);

        if (isset($expected['failure'])) {
            self::assertSame(':', $url->protocol);
            self::assertSame($expected['input'], $url->href);

            return;
        }

        self::assertSame($expected['href'], $url->href);
        self::assertSame($expected['protocol'], $url->protocol);
        self::assertSame($expected['username'], $url->username);
        self::assertSame($expected['password'], $url->password);
        self::assertSame($expected['host'], $url->host);
        self::assertSame($expected['hostname'], $url->hostname);
        self::assertSame($expected['port'], $url->port);
        self::assertSame($expected['pathname'], $url->pathname);
        self::assertSame($expected['search'], $url->search);
        self::assertSame($expected['hash'], $url->hash);
    }

    public function urlTestDataProvider(): Generator
    {
        foreach ($this->decodeUrlTestData() as $data) {
            if (isset($data['base'])) {
                yield [$data];
            }
        }
    }
}
