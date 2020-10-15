<?php

declare(strict_types=1);

namespace Rowbot\DOM\URL;

use Rowbot\URL\BasicURLParser;
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
     *
     * @return \Rowbot\URL\URLRecord|false
     */
    public static function parseUrl(
        string $input,
        URLRecord $base = null,
        string $encodingOverride = null
    ) {
        $url = BasicURLParser::parseBasicUrl($input, $base, $encodingOverride);

        if ($url === false) {
            return false;
        }

        if ($url->scheme !== 'blob') {
            return $url;
        }

        // TODO: If the first string in url’s path is not in the blob URL store,
        // return url

        // TODO: Set url’s object to a structured clone of the entry in the blob
        // URL store corresponding to the first string in url’s path

        return $url;
    }
}
