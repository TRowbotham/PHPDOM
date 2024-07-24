<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url\resources;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_key_exists;
use function is_string;
use function str_starts_with;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/resources/a-element-origin.js
 */
trait AElementOriginTrait
{
    use DataProviderTrait;

    #[DataProvider('urlOriginTestProvider')]
    public function testUrlOrigin(array $expected): void
    {
        // We cannot use a null base for HTML tests
        $base = $expected['base'] ?? 'about:blank';
        $url = $this->bURL($expected['input'], $base);
        self::assertSame($expected['origin'], $url->origin);
    }

    public static function urlOriginTestProvider(): Generator
    {
        foreach (self::decodeUrlTestData() as $data) {
            // Skip comments and tests without "origin" expectation
            if (is_string($data) || !array_key_exists('origin', $data)) {
                continue;
            }

            // Fragments are relative against "about:blank" (this might always be redundant due to requiring "origin" in expected)
            if ($data['base'] === null && str_starts_with($data['input'], '#')) {
                continue;
            }

            // HTML special cases data: and javascript: URLs in <base>
            if (
                $data['base'] !== null
                && (str_starts_with($data['base'], 'data:') || str_starts_with($data['base'], 'javascript:'))
            ) {
                continue;
            }

            yield [$data];
        }
    }
}
