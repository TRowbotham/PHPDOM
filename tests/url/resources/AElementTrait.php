<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url\resources;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function is_string;
use function str_starts_with;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/resources/a-element.js
 */
trait AElementTrait
{
    use DataProviderTrait;

    #[DataProvider('urlTestDataProvider')]
    public function testUrl(array $expected): void
    {
        // We cannot use a null base for HTML tests
        $base = $expected['base'] ?? 'about:blank';
        $url = $this->bURL($expected['input'], $base);

        if (isset($expected['failure']) && $expected['failure']) {
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

    public static function urlTestDataProvider(): Generator
    {
        foreach (self::decodeUrlTestData() as $data) {
            // Skip comments
            if (is_string($data)) {
                continue;
            }

            // Fragments are relative against "about:blank"
            if (isset($data['relativeTo']) && $data['relativeTo'] === 'any-base') {
                continue;
            }

            if (
                $data['base'] !== null
                && (str_starts_with($data['base'], 'data:') || str_starts_with($data['base'], 'javascript:'))
            ) {
                continue;
            }

            if ($data['base'] === null) {
                continue;
            }

            yield [$data];
        }
    }
}
