<?php

declare(strict_types=1);

namespace Rowbot\DOM\URL;

use Rowbot\URL\BasicURLParser;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\URLRecord;

final class URLParser
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Parses a url string.
     *
     * @see https://url.spec.whatwg.org/#concept-url-parser
     */
    public static function parseUrl(string $input, ?URLRecord $base = null, ?string $encoding = null): ?URLRecord
    {
        // 1. Let url be the result of running the basic URL parser on input with base and encoding.
        $parser = new BasicURLParser();
        $url = $parser->parse(new Utf8String($input), $base, $encoding);

        // 2. If url is failure, return failure.
        if ($url === false) {
            return null;
        }

        // 3. If url’s scheme is not "blob", return url.
        if (!$url->scheme->isBlob()) {
            return $url;
        }

        // TODO: Set url’s blob URL entry to the result of resolving the blob URL url, if that did not return failure,
        // and null otherwise.

        // Return url.
        return $url;
    }
}
