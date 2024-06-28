<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\url\resources;

use Rowbot\DOM\Element\HTML\HTMLAnchorElement;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function array_filter;
use function file_get_contents;
use function json_decode;

use const DIRECTORY_SEPARATOR;

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
        // Replace all unpaired surrogate escape sequences with a \uFFFD escape sequence to avoid
        // json_decode() having a stroke and emitting a JSON_ERROR_UTF16 error causing the decode
        // to fail
        $body = preg_replace(
            '/
                (?(DEFINE)
                    (?<high>\\\u[Dd][89AaBb][[:xdigit:]][[:xdigit:]])
                    (?<low>\\\u[Dd][C-Fc-f][[:xdigit:]][[:xdigit:]])
                )

                # Match a low surrogate not preceded by a high surrogate
                (?<!(?&high))(?&low)

                # Match a high surrogate not followed by a low surrogate
                |(?&high)(?!(?&low))
            /x',
            '\\uFFFD',
            $body
        );

        // Remove comments and check to make sure it is valid JSON.
        $this->urltestdata = array_filter(json_decode($body, true, flags: JSON_THROW_ON_ERROR), 'is_array');;

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
