<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url;

use PHPUnit\Framework\Attributes\DataProvider;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use RuntimeException;

use function file_get_contents;
use function json_decode;
use function json_last_error;

use const DIRECTORY_SEPARATOR as DS;
use const JSON_ERROR_NONE;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-setters.html
 */
class URLSettersTest extends TestCase
{
    use DocumentGetter;

    private static array $testData = [];

    #[DataProvider('settersDataProvider')]
    public function testUrlSettersOnAnchorElement(array $input): void
    {
        $url = self::getHTMLDocument()->createElement('a');
        $url->href = $input['href'];
        $url->{$input['setter']} = $input['new_value'];

        foreach ($input['expected'] as $attribute => $value) {
            self::assertSame($value, $url->{$attribute}, $attribute);
        }
    }

    #[DataProvider('settersDataProvider')]
    public function testUrlSettersOnAreaElement(array $input): void
    {
        $url = self::getHTMLDocument()->createElement('area');
        $url->href = $input['href'];
        $url->{$input['setter']} = $input['new_value'];

        foreach ($input['expected'] as $attribute => $value) {
            self::assertSame($value, $url->{$attribute}, $attribute);
        }
    }

    public static function settersDataProvider(): iterable
    {
        if (self::$testData !== []) {
            return self::$testData;
        }

        $data = file_get_contents(__DIR__ . DS . 'resources' . DS . 'setters_tests.json');
        $json = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException();
        }

        foreach ($json as $key => $inputs) {
            if ($key === 'comment') {
                continue;
            }

            foreach ($inputs as $data) {
                unset($data['comment']);
                $data['setter'] = $key;
                self::$testData[] = [$data];
            }
        }

        return self::$testData;
    }
}
