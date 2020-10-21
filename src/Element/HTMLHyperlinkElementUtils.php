<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\URL\BasicURLParser;
use Rowbot\URL\Component\PathList;
use Rowbot\URL\State\FragmentState;
use Rowbot\URL\State\HostnameState;
use Rowbot\URL\State\HostState;
use Rowbot\URL\State\PathStartState;
use Rowbot\URL\State\PortState;
use Rowbot\URL\State\QueryState;
use Rowbot\URL\State\SchemeStartState;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\IDLString;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#htmlhyperlinkelementutils
 */
trait HTMLHyperlinkElementUtils
{
    /**
     * @var \Rowbot\URL\URLRecord|null
     */
    private $url;

    /**
     * Gets the Element's href IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-href
     */
    protected function getHref(): string
    {
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

    /**
     * Sets the Element's href content attribute to the given value.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-href
     */
    protected function setHref(string $value): void
    {
        $this->attributeList->setAttrValue('href', $value);
    }

    /**
     * Gets the Element's origin IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-origin
     */
    protected function getOrigin(): string
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 2. If this element's url is null, return the empty string.
        if ($this->url === null) {
            return '';
        }

        // 3. Return the serialization of this element's url's origin.
        return (string) $this->url->getOrigin();
    }

    /**
     * Gets the Element's protocol IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-protocol
     */
    protected function getProtocol(): string
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 2. If this element's url is null, return ":".
        if ($this->url === null) {
            return ':';
        }

        // 3. Return this element's url's scheme, followed by ":".
        return $this->url->scheme . ':';
    }

    /**
     * Sets the Element's URL's protocol and updates the Element's href content
     * attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-protocol
     */
    protected function setProtocol(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 2. If this element's url is null, terminate these steps.
        if ($this->url === null) {
            return;
        }

        // 3. Basic URL parse the given value, followed by ":", with this element's url as url and
        // scheme start state as state override.
        $input = new IDLString($value);
        $parser = new BasicURLParser();
        $parser->parse($input->append(':'), null, null, $this->url, new SchemeStartState());

        // 4. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's username IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-username
     */
    protected function getUsername(): string
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 2. If this element's url is null, return the empty string.
        if ($this->url === null) {
            return '';
        }

        // 3. Return this element's url's username.
        return $this->url->username;
    }

    /**
     * Sets the Element's URL's username and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-username
     */
    protected function setUsername(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null or url cannot have a username/password/port, then return.
        if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
            return;
        }

        // 4. Set the username, given url and the given value.
        $this->setUrlUsername($value);

        // 5. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's password IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-password
     */
    protected function getPassword(): string
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null, then return the empty string.
        if ($this->url === null) {
            return '';
        }

        // 4. Return url's password.
        return $this->url->password;
    }

    /**
     * Sets the Element's URL's password and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-password
     */
    protected function setPassword(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null or url cannot have a username/password/port, then return.
        if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
            return;
        }

        // 4. Set the password, given url and the given value.
        $this->setUrlPassword($value);

        // 5. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's host IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-host
     */
    protected function getHost(): string
    {
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

    /**
     * Sets the Element's URL's host and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-host
     */
    protected function setHost(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null or url's cannot-be-a-base-URL flag is set, terminate these steps.
        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        // 4. Basic URL parse the given value, with url as url and host state as state override.
        $parser = new BasicURLParser();
        $parser->parse(new IDLString($value), null, null, $this->url, new HostState());

        // 5. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's hostname IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hostname
     */
    protected function getHostname(): string
    {
        // 1. Reinitialize url.
        $this->reinitialiseURL();

        // 3. If url or url's host is null, return the empty string.
        if ($this->url === null || $this->url->host->isNull()) {
            return '';
        }

        // 4. Return url's host, serialized.
        return $this->url->host->getSerializer()->toFormattedString();
    }

    /**
     * Sets the Element's URL's hostname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hostname
     */
    protected function setHostname(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null or url's cannot-be-a-base-URL flag is set, terminate these steps.
        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        // 4. Basic URL parse the given value, with url as url and hostname state as state override.
        $parser = new BasicURLParser();
        $parser->parse(new IDLString($value), null, null, $this->url, new HostnameState());

        // 5. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's prot IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-port
     */
    protected function getPort(): string
    {
        // 1. Reinitialize url.
        $this->reinitialiseURL();

        // 3. If url or url's port is null, return the empty string.
        if ($this->url === null || $this->url->port === null) {
            return '';
        }

        // Return url's port, serialized.
        return (string) $this->url->port;
    }

    /**
     * Sets the Element's URL's port and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-port
     */
    protected function setPort(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null or url cannot have a username/password/port, then return.
        if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
            return;
        }

        $input = new IDLString($value);

        // 4. If the given value is the empty string, then set url's port to null.
        if ($input->isEmpty()) {
            $this->url->port = null;

        // 5. Otherwise, basic URL parse the given value, with url as url and port state as state
        // override.
        } else {
            $parser = new BasicURLParser();
            $parser->parse($input, null, null, $this->url, new PortState());
        }

        // 5. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's pathname IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-pathname
     */
    protected function getPathname(): string
    {
        // 1. Reinitialize url.
        $this->reinitialiseURL();

        // 3. If url is null, return the empty string.
        if ($this->url === null) {
            return '';
        }

        // 4. If url's cannot-be-a-base-URL flag is set, return the first string in url's path.
        if ($this->url->cannotBeABaseUrl) {
            return (string) $this->url->path->first();
        }

        // 5. If url's path is empty, then return the empty string.
        if ($this->url->path->isEmpty()) {
            return '';
        }

        // 6. Return "/", followed by the strings in url's path (including empty strings), separated
        // from each other by "/".
        return '/' . $this->url->path;
    }

    /**
     * Sets the Element's URL's pathname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-pathname
     */
    protected function setPathname(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null or url's cannot-be-a-base-URL flag is set, terminate these steps.
        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        // 4. Set url's path to the empty list.
        $this->url->path = new PathList();

        // 5. Basic URL parse the given value, with url as url and path start state as state
        // override.
        $parser = new BasicURLParser();
        $parser->parse(new IDLString($value), null, null, $this->url, new PathStartState());

        // 6. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's search IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-search
     */
    protected function getSearch(): string
    {
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

    /**
     * Sets the Element's URL's search and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-search
     */
    protected function setSearch(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null, terminate these steps.
        if ($this->url === null) {
            return;
        }

        $input = new IDLString($value);

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

            // 5.3 Basic URL parse input, with url as url and query state as state override, and
            // this element's node document's document's character encoding as encoding override.
            $parser = new BasicURLParser();
            $parser->parse(
                $input,
                null,
                $this->nodeDocument->characterSet,
                $this->url,
                new QueryState()
            );
        }

        // 6. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
    }

    /**
     * Gets the Element's hash IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hash
     */
    protected function getHash(): string
    {
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

    /**
     * Sets the Element's URL's hash and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hash
     */
    protected function setHash(string $value): void
    {
        // 1. Reinitialize url.
        $this->reinitialiseUrl();

        // 3. If url is null, then return.
        if ($this->url === null) {
            return;
        }

        $input = new IDLString($value);

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
            $parser->parse($input, null, null, $this->url, new FragmentState());
        }

        // 6. Update href.
        $this->attributeList->setAttrValue('href', $this->url->serializeURL());
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
        // 1. If element's url is non-null, its scheme is "blob", and its cannot-be-a-base-URL flag
        // is set, terminate these steps.
        if ($this->url && $this->url->scheme->isBlob() && $this->url->cannotBeABaseUrl) {
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
        $href = $this->attributeList->getAttrByNamespaceAndLocalName(null, 'href');

        // 1. If this element's href content attribute is absent, set this element's url to null.
        if (!$href) {
            $this->url = null;

            return;
        }

        // 2. Otherwise, parse this element's href content attribute value relative to this
        // element's node document. If parsing is successful, set this element's url to the result;
        // otherwise, set this element's url to null.
        $url = $this->parseURL($href->value, $this->nodeDocument);
        $this->url = $url === false ? null : $url['urlRecord'];
    }

    /**
     * @see https://url.spec.whatwg.org/#set-the-password
     */
    private function setUrlPassword(string $input): void
    {
        $this->url->password = '';

        foreach (new IDLString($input) as $codePoint) {
            $this->url->password .= CodePoint::utf8PercentEncode(
                $codePoint,
                CodePoint::USERINFO_PERCENT_ENCODE_SET
            );
        }
    }

    /**
     * @see https://url.spec.whatwg.org/#set-the-username
     */
    private function setUrlUsername(string $input): void
    {
        $this->url->username = '';

        foreach (new IDLString($input) as $codePoint) {
            $this->url->username .= CodePoint::utf8PercentEncode(
                $codePoint,
                CodePoint::USERINFO_PERCENT_ENCODE_SET
            );
        }
    }
}
