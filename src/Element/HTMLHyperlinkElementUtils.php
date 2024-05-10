<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\DOM\InternalEvent\AttributeChangedEvent;
use Rowbot\URL\BasicURLParser;
use Rowbot\URL\Component\PathList;
use Rowbot\URL\ParserState;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\URLRecord;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#htmlhyperlinkelementutils
 */
trait HTMLHyperlinkElementUtils
{
    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-href
     */
    public string $href {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null and this element has no href content attribute, return the empty string.
            if ($this->url === null) {
                $href = $this->attributeList->getAttrByNamespaceAndLocalName(null, 'href');

                if (!$href) {
                    return '';
                }

                // 4. Otherwise, if url is null, return this element's href content attribute's value.
                return $href->value;
            }

            // 5. Return url, serialized.
            return $this->url->serializeURL();
        }
        set(string $value) {
            $this->attributeList->setAttrValue('href', (string) $value);
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-origin
     */
    public string $origin {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 2. If this element's url is null, return the empty string.
            if ($this->url === null) {
                return '';
            }

            // 3. Return the serialization of this element's url's origin.
            return (string) $this->url->getOrigin();
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-protocol
     */
    public string $protocol {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 2. If this element's url is null, return ":".
            if ($this->url === null) {
                return ':';
            }

            // 3. Return this element's url's scheme, followed by ":".
            return $this->url->scheme . ':';
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 2. If this's url is null, then return.
            if ($this->url === null) {
                return;
            }

            // 3. Basic URL parse the given value, followed by ":", with this element's url as url and
            // scheme start state as state override.
            $input = new Utf8String($value);
            $parser = new BasicURLParser();
            $parser->parse($input->append(':'), null, null, $this->url, ParserState::SCHEME_START);

            // 4. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-username
     */
    public string $username {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 2. If this element's url is null, return the empty string.
            if ($this->url === null) {
                return '';
            }

            // 3. Return this element's url's username.
            return $this->url->username;
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null or url cannot have a username/password/port, then return.
            if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            // 4. Set the username, given url and the given value.
            $this->url->setUsername(new Utf8String($value));

            // 5. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-password
     */
    public string $password {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null, then return the empty string.
            if ($this->url === null) {
                return '';
            }

            // 4. Return url's password.
            return $this->url->password;
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null or url cannot have a username/password/port, then return.
            if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            // 4. Set the password, given url and the given value.
            $this->url->setPassword(new Utf8String($value));

            // 5. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-host
     */
    public string $host {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseURL();

            // 3. If url or url's host is null, return the empty string.
            if ($this->url === null || $this->url->host->isNull()) {
                return '';
            }

            $serializer = $this->url->host->getSerializer();

            // 4. If url's port is null, return url's host, serialized.
            if ($this->url->port === null) {
                return $serializer->toFormattedString();
            }

            // 5. Return url's host, serialized, followed by ":" and url's port, serialized.
            return $serializer->toFormattedString() . ':' . $this->url->port;
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null or url has an opaque path, then return.
            if ($this->url === null || $this->url->path->isOpaque()) {
                return;
            }

            // 4. Basic URL parse the given value, with url as url and host state as state override.
            $parser = new BasicURLParser();
            $parser->parse(new Utf8String($value), null, null, $this->url, ParserState::HOST);

            // 5. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hostname
     */
    public string $hostname {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseURL();

            // 3. If url or url's host is null, return the empty string.
            if ($this->url === null || $this->url->host->isNull()) {
                return '';
            }

            // 4. Return url's host, serialized.
            return $this->url->host->getSerializer()->toFormattedString();
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null or url has an opaque path, then return.
            if ($this->url === null || $this->url->path->isOpaque()) {
                return;
            }

            // 4. Basic URL parse the given value, with url as url and hostname state as state override.
            $parser = new BasicURLParser();
            $parser->parse(new Utf8String($value), null, null, $this->url, ParserState::HOSTNAME);

            // 5. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-port
     */
    public string $port {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseURL();

            // 3. If url or url's port is null, return the empty string.
            if ($this->url === null || $this->url->port === null) {
                return '';
            }

            // Return url's port, serialized.
            return (string) $this->url->port;
        }
        set(int|string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null or url cannot have a username/password/port, then return.
            if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            $input = new Utf8String((string) $value);

            // 4. If the given value is the empty string, then set url's port to null.
            if ($input->isEmpty()) {
                $this->url->port = null;

            // 5. Otherwise, basic URL parse the given value, with url as url and port state as state
            // override.
            } else {
                $parser = new BasicURLParser();
                $parser->parse($input, null, null, $this->url, ParserState::PORT);
            }

            // 5. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-pathname
     */
    public string $pathname {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseURL();

            // 3. If url is null, return the empty string.
            if ($this->url === null) {
                return '';
            }

            // 4. Return the result of URL path serializing url.
            return (string) $this->url->path;
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null or url has an opaque path, then return.
            if ($this->url === null || $this->url->path->isOpaque()) {
                return;
            }

            // 4. Set url's path to the empty list.
            $this->url->path = new PathList();

            // 5. Basic URL parse the given value, with url as url and path start state as state
            // override.
            $parser = new BasicURLParser();
            $parser->parse(new Utf8String($value), null, null, $this->url, ParserState::PATH_START);

            // 6. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-search
     */
    public string $search {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseURL();

            // 3. If url is null, or url's query is either null or the empty string, return the empty
            // string.
            if ($this->url === null || $this->url->query === null || $this->url->query === '') {
                return '';
            }

            // 4. Return "?", followed by url's query.
            return '?' . $this->url->query;
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null, terminate these steps.
            if ($this->url === null) {
                return;
            }

            $input = new Utf8String($value);

            // 4. If the given value is the empty string, set url's query to null.
            if ($input->isEmpty()) {
                $this->url->query = null;

            // 5. Otherwise:
            } else {
                // 5.1 Let input be the given value with a single leading "?" removed, if any.
                if ($input->startsWith('?')) {
                    $input = $input->substr(1);
                }

                // 5.2 Set url's query to the empty string.
                $this->url->query = '';

                // 5.3 Basic URL parse input, with url as url and query state as state override.
                $parser = new BasicURLParser();
                $parser->parse($input, null, null, $this->url, ParserState::QUERY);
            }

            // 6. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hash
     */
    public string $hash {
        get {
            // 1. Reinitialize url.
            $this->reinitialiseURL();

            // 3. If url is null, or url's fragment is either null or the empty string, return the empty
            // string.
            if ($this->url === null || $this->url->fragment === null || $this->url->fragment === '') {
                return '';
            }

            // 4. Return "#", followed by url's fragment.
            return '#' . $this->url->fragment;
        }
        set(string $value) {
            // 1. Reinitialize url.
            $this->reinitialiseUrl();

            // 3. If url is null, then return.
            if ($this->url === null) {
                return;
            }

            $input = new Utf8String($value);

            // 4. If the given value is the empty string, set url's fragment to null.
            if ($input->isEmpty()) {
                $this->url->fragment = null;

            // 5. Otherwise:
            } else {
                // 5.1 Let input be the given value with a single leading "#" removed, if any.
                if ($input->startsWith('#')) {
                    $input = $input->substr(1);
                }

                // 5.2 Set url's fragment to the empty string.
                $this->url->fragment = '';

                // 5.3 Basic URL parse input, with url as url and fragment state as state override.
                $parser = new BasicURLParser();
                $parser->parse($input, null, null, $this->url, ParserState::FRAGMENT);
            }

            // 6. Update href.
            $this->attributeList->setAttrValue('href', $this->url->serializeURL());
        }
    }

    private ?URLRecord $url;

    /**
     * @internal
     */
    public function onHrefAttributeChanged(AttributeChangedEvent $event): void
    {
        if ($event->localName !== 'href' || $event->namespace !== null) {
            return;
        }

        $this->setURL();
    }

    /**
     * Reintialises the Element's URL.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#reinitialise-url
     */
    protected function reinitialiseUrl(): void
    {
        // 1. If element's url is non-null, its scheme is "blob", and it has an opaque path, then terminate these steps.
        if ($this->url && $this->url->scheme->isBlob() && $this->url->path->isOpaque()) {
            return;
        }

        // 2. Set the url.
        $this->setURL();
    }

    /**
     * Sets this Element's URL to the result of parsing it's href content
     * attribute or null if parsing fails. This method must be run any time
     * an Element that implements this trait is created or anytime the Element's
     * href content attribute is added, changed, or removed.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#concept-hyperlink-url-set
     */
    protected function setURL(): void
    {
        // 1. Set this element's url to null.
        $this->url = null;

        // 2. If this element's href content attribute is absent, then return.
        $href = $this->attributeList->getAttrByNamespaceAndLocalName(null, 'href');

        if ($href === null) {
            return;
        }

        // 3. Let url be the result of encoding-parsing a URL given this element's href content attribute's value,
        // relative to this element's node document.
        $url = URLResolver::encodingParseURL($href->value, $this->nodeDocument);

        // 4. If url is not failure, then set this element's url to url.
        if ($url !== null) {
            $this->url = $url;
        }
    }
}
