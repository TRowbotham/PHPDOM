<?php
namespace Rowbot\DOM\URL;

use Rowbot\URL\BasicURLParser;

final class URLParser
{
    private function __construct()
    {
    }

    public static function parseUrl(
        $input,
        URLRecord $base = null,
        $encodingOverride = null
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
