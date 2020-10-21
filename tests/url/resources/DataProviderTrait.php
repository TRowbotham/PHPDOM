<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url\resources;

use Rowbot\DOM\Element\HTML\HTMLAnchorElement;
use Rowbot\DOM\Tests\dom\WindowTrait;
use RuntimeException;

use function file_get_contents;
use function hexdec;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function substr_replace;

use const JSON_ERROR_NONE;
use const PREG_OFFSET_CAPTURE;

trait DataProviderTrait
{
    use WindowTrait;

    private $urltestdata = [];

    public function decodeUrlTestData(): array
    {
        if ($this->urltestdata !== []) {
            return $this->urltestdata;
        }

        $body = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'urltestdata.json');
        $offset = 0;

        // Replace all unpaired surrogate escape sequences with a \uFFFD escape sequence to avoid
        // json_decode() having a stroke and emitting a JSON_ERROR_UTF16 error causing the decode
        // to fail
        while (preg_match('/\\\u([[:xdigit:]]{4})/', $body, $matches, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $codePoint1 = hexdec($matches[1][0]);
            $offset = $matches[0][1] + 6;

            if ($codePoint1 >= 0xD800 && $codePoint1 <= 0xDBFF) {
                // There is no following code point, so replace it with a \uFFFD
                if (
                    preg_match(
                        '/\G\\\u([[:xdigit:]]{4})/',
                        $body,
                        $m,
                        PREG_OFFSET_CAPTURE,
                        $matches[1][1] + 4
                    ) !== 1
                ) {
                    $body = substr_replace($body, '\\uFFFD', $matches[0][1], 6);

                    continue;
                }

                $codePoint2 = hexdec($m[1][0]);

                // If next code point is not a low surrogate, replace it with a \uFFFD
                if ($codePoint2 < 0xDC00 || $codePoint2 > 0xDFFF) {
                    $body = substr_replace($body, '\\uFFFD', $matches[0][1], 6);

                    continue;
                }

                $offset += 6;
            } elseif ($codePoint1 >= 0xDC00 && $codePoint1 <= 0xDFFF) {
                // lone low surrogate, replace it with a \uFFFD
                $body = substr_replace($body, '\\uFFFD', $matches[0][1], 6);
            }
        }

        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode json. "' . json_last_error_msg() . '"');
        }

        // Filter out comments
        $this->urltestdata = array_filter($json, 'is_array');

        return $this->urltestdata;
    }

    public function bURL(string $url, ?string $base): HTMLAnchorElement
    {
        $base = $base ?? 'about:blank';
        $document = self::getWindow()->document;
        $document->getElementById('base')->href = $base;
        $a = $document->createElement('a');
        $a->setAttribute('href', $url);

        return $a;
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'html';
    }
}
