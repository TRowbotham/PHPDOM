<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\Document;
use Rowbot\DOM\URL\URLParser;
use Rowbot\URL\URLRecord;

/**
 * @see https://html.spec.whatwg.org/multipage/urls-and-fetching.html#resolving-urls
 */
final class URLResolver
{
    /**
     * @see https://html.spec.whatwg.org/multipage/urls-and-fetching.html#parse-a-url
     */
    public static function parseURL(string $url, Document $environment): ?URLRecord
    {
        // 1. Let baseURL be environment's base URL, if environment is a Document object; otherwise environment's API
        // base URL.
        $baseURL = $environment->getBaseURL();

        // 2. Return the result of applying the URL parser to url, with baseURL.
        return URLParser::parseUrl($url, $baseURL);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/urls-and-fetching.html#encoding-parsing-a-url
     */
    public static function encodingParseURL(string $url, Document $environment): ?URLRecord
    {
        // 1. Let encoding be UTF-8.
        $encoding = 'UTF-8';

        // 2. If environment is a Document object, then set encoding to environment's character encoding.
        if ($environment instanceof Document) {
            $encoding = $environment->characterSet;
        }

        // 4. Let baseURL be environment's base URL, if environment is a Document object; otherwise environment's API
        // base URL.
        $baseURL = $environment->getBaseURL();

        // 5. Return the result of applying the URL parser to url, with baseURL and encoding.
        return URLParser::parseUrl($url, $baseURL, $encoding);
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/urls-and-fetching.html#encoding-parsing-and-serializing-a-url
     */
    public static function encodingParseAndSerializeURL(string $url, Document $environment): ?string
    {
        // 1. Let url be the result of encoding-parsing a URL given url, relative to environment.
        $url = self::encodingParseURL($url, $environment);

        // 2. If url is failure, then return failure.
        if ($url === null) {
            return null;
        }

        // 3. Return the result of applying the URL serializer to url.
        return $url->serializeURL();
    }
}
