<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element;

use Rowbot\URL\BasicURLParser;

use function implode;
use function mb_substr;

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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            $href = $this->attributeList->getAttrByNamespaceAndLocalName(
                null,
                'href'
            );

            if (!$href) {
                return '';
            }

            return $href->value;
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return '';
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return ':';
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return;
        }

        BasicURLParser::parseBasicUrl(
            $value . ':',
            null,
            null,
            $this->url,
            BasicURLParser::SCHEME_START_STATE
        );
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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return '';
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
            return;
        }

        $this->url->setUsername($value);
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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return '';
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
            return;
        }

        $this->url->setPassword($value);
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
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->host->isNull()) {
            return '';
        }

        if ($this->url->port === null) {
            return (string) $this->url->host;
        }

        return $this->url->host . ':' . $this->url->port;
    }

    /**
     * Sets the Element's URL's host and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-host
     */
    protected function setHost(string $value): void
    {
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        BasicURLParser::parseBasicUrl(
            $value,
            null,
            null,
            $this->url,
            BasicURLParser::HOST_STATE
        );
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
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->host->isNull()) {
            return '';
        }

        return (string) $this->url->host;
    }

    /**
     * Sets the Element's URL's hostname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hostname
     */
    protected function setHostname(string $value): void
    {
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        BasicURLParser::parseBasicUrl(
            $value,
            null,
            null,
            $this->url,
            BasicURLParser::HOSTNAME_STATE
        );
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
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->port === null) {
            return '';
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotHaveUsernamePasswordPort()) {
            return;
        }

        if ($value === '') {
            $this->url->port = null;
        } else {
            BasicURLParser::parseBasicUrl(
                $value,
                null,
                null,
                $this->url,
                BasicURLParser::PORT_STATE
            );
        }

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
        $this->reinitialiseURL();

        if ($this->url === null) {
            return '';
        }

        if ($this->url->cannotBeABaseUrl) {
            return $this->url->path[0];
        }

        if ($this->url->path === []) {
            return '';
        }

        return '/' . implode('/', $this->url->path);
    }

    /**
     * Sets the Element's URL's pathname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-pathname
     */
    protected function setPathname(string $value): void
    {
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        $this->url->path = [];
        BasicURLParser::parseBasicUrl(
            $value,
            null,
            null,
            $this->url,
            BasicURLParser::PATH_START_STATE
        );
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
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->query === null || $this->url->query === '') {
            return '';
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return;
        }

        if ($value === '') {
            $this->url->query = null;
        } else {
            $input = $value;

            if (mb_substr($value, 0, 1, 'utf-8') === '?') {
                $input = mb_substr($value, 1, null, 'utf-8');
            }

            $this->url->query = '';
            BasicURLParser::parseBasicUrl(
                $input,
                null,
                $this->nodeDocument->characterSet,
                $this->url,
                BasicURLParser::QUERY_STATE
            );
        }

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
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->fragment === null || $this->url->fragment === '') {
            return '';
        }

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
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return;
        }

        if ($value === '') {
            $this->url->fragment = null;
        } else {
            $input = $value;

            if (mb_substr($value, 0, 1, 'utf-8') === '#') {
                $input = mb_substr($value, 1, null, 'utf-8');
            }

            $this->url->fragment = '';
            BasicURLParser::parseBasicUrl(
                $input,
                null,
                null,
                $this->url,
                BasicURLParser::FRAGMENT_STATE
            );
        }

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
        if ($this->url && $this->url->scheme === 'blob' && $this->url->cannotBeABaseUrl) {
            // Terminate these steps
            return;
        }

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

        // If this element's href content attribute is absent, set this
        // element's url to null.
        if (!$href) {
            $this->url = null;

            return;
        }

        // Otherwise, parse this element's href content attribute value relative
        // to this element's node document.
        $url = $this->parseURL($href->value, $this->nodeDocument);

        // If parsing is successful, set this element's url to the result;
        // otherwise, set this element's url to null.
        $this->url = $url === false ? null : $url['urlRecord'];
    }
}
