<?php
namespace Rowbot\DOM\Element;

use Rowbot\DOM\URL\HostFactory;
use Rowbot\DOM\URL\URLParser;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#htmlhyperlinkelementutils
 */
trait HTMLHyperlinkElementUtils
{
    private $url;

    /**
     * Gets the Element's href IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-href
     *
     * @return string
     */
    protected function getHref()
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
     *
     * @param string $value A URL.
     */
    protected function setHref($value)
    {
        $this->attributeList->setAttrValue('href', $value);
    }

    /**
     * Gets the Element's origin IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-origin
     *
     * @return string
     */
    protected function getOrigin()
    {
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return '';
        }

        return $this->url->getOrigin()->serializeAsUnicode();
    }

    /**
     * Gets the Element's protocol IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-protocol
     *
     * @return string
     */
    protected function getProtocol()
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
     *
     * @param string $value A URL scheme.
     */
    protected function setProtocol($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return;
        }

        URLParser::parseBasicUrl(
            $value . ':',
            null,
            null,
            $this->url,
            URLParser::SCHEME_START_STATE
        );
        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's username IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-username
     *
     * @return string
     */
    protected function getUsername()
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
     *
     * @param string $value A username.
     */
    protected function setUsername($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null ||
            $this->url->host === null ||
            $this->url->cannotBeABaseUrl
        ) {
            return;
        }

        $this->url->setUsername($value);
        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's password IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-password
     *
     * @return string
     */
    protected function getPassword()
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
     *
     * @param string $value A password.
     */
    protected function setPassword($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null ||
            $this->url->host === null ||
            $this->url->cannotBeABaseUrl
        ) {
            return;
        }

        $this->url->setPassword($value);
        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's host IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-host
     *
     * @return string
     */
    protected function getHost()
    {
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->host === null) {
            return '';
        }

        if ($this->url->port === null) {
            return HostFactory::serialize($this->url->host);
        }

        return HostFactory::serialize($this->url->host) . ':' .
            $this->url->port;
    }

    /**
     * Sets the Element's URL's host and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-host
     *
     * @param string $value A host.
     */
    protected function setHost($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        URLParser::parseBasicUrl(
            $value,
            null,
            null,
            $this->url,
            URLParser::HOST_STATE
        );
        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's hostname IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hostname
     *
     * @return string
     */
    protected function getHostname()
    {
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->host === null) {
            return '';
        }

        return HostFactory::serialize($this->url->host);
    }

    /**
     * Sets the Element's URL's hostname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hostname
     *
     * @param string $value A hostname.
     */
    protected function setHostname($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        URLParser::parseBasicUrl(
            $value,
            null,
            null,
            $this->url,
            URLParser::HOSTNAME_STATE
        );
        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's prot IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-port
     *
     * @return string
     */
    protected function getPort()
    {
        $this->reinitialiseURL();

        if ($this->url === null || $this->url->port === null) {
            return '';
        }

        return $this->url->port;
    }

    /**
     * Sets the Element's URL's port and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-port
     *
     * @param string $value A port.
     */
    protected function setPort($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null ||
            $this->url->host === null ||
            $this->url->cannotBeABaseUrl ||
            $this->url->scheme === 'file'
        ) {
            return;
        }

        if ($value === '') {
            $this->url->port = null;
        } else {
            URLParser::parseBasicUrl(
                $value,
                null,
                null,
                $this->url,
                URLParser::PORT_STATE
            );
        }

        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's pathname IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-pathname
     *
     * @return string
     */
    protected function getPathname()
    {
        $this->reinitialiseURL();

        if ($this->url === null) {
            return '';
        }

        if ($this->url->cannotBeABaseUrl) {
            return $this->url->path[0];
        }

        if (empty($this->url->path)) {
            return '';
        }

        return '/' . implode('/', $this->url->path);
    }

    /**
     * Sets the Element's URL's pathname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-pathname
     *
     * @param string $value A path.
     */
    protected function setPathname($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->cannotBeABaseUrl) {
            return;
        }

        $this->url->path = [];
        URLParser::parseBasicUrl(
            $value,
            null,
            null,
            $this->url,
            URLParser::PATH_START_STATE
        );
        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's search IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-search
     *
     * @return string
     */
    protected function getSearch()
    {
        $this->reinitialiseURL();

        if ($this->url === null ||
            $this->url->query === null ||
            $this->url->query === ''
        ) {
            return '';
        }

        return '?' . $this->url->query;
    }

    /**
     * Sets the Element's URL's search and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-search
     *
     * @param string $value A URL query.
     */
    protected function setSearch($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null) {
            return;
        }

        if ($value === '') {
            $this->url->query = null;
        } else {
            $input = $value;

            if (mb_substr($value, 0, 1) === '?') {
                $input = mb_substr($value, 1);
            }

            $this->url->query = '';
            URLParser::parseBasicUrl(
                $input,
                null,
                $this->nodeDocument->characterSet,
                $this->url,
                URLParser::QUERY_STATE
            );
        }

        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Gets the Element's hash IDL attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hash
     *
     * @return string
     */
    protected function getHash()
    {
        $this->reinitialiseURL();

        if ($this->url === null ||
            $this->url->fragment === null ||
            $this->url->fragment === ''
        ) {
            return '';
        }

        return '#' . $this->url->fragment;
    }

    /**
     * Sets the Element's URL's hash and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hash
     *
     * @param string $value A URL fragment.
     */
    protected function setHash($value)
    {
        $this->reinitialiseUrl();

        if ($this->url === null || $this->url->scheme === 'javascript') {
            return;
        }

        if ($value === '') {
            $this->url->fragment = null;
        } else {
            $input = $value;

            if (mb_substr($value, 0, 1) === '#') {
                $input = mb_substr($value, 1);
            }

            $this->url->fragment = '';
            URLParser::parseBasicUrl(
                $input,
                null,
                null,
                $this->url,
                URLParser::FRAGMENT_STATE
            );
        }

        $this->attributeList->setAttrValue(
            'href',
            $this->url->serializeURL()
        );
    }

    /**
     * Reintialises the Element's URL.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#reinitialise-url
     */
    protected function reinitialiseUrl()
    {
        if ($this->url &&
            $this->url->scheme === 'blob' &&
            $this->url->cannotBeABaseUrl
        ) {
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
    protected function setURL()
    {
        $href = $this->attributeList->getAttrByNamespaceAndLocalName(
            null,
            'href'
        );

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
