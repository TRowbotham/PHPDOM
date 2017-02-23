<?php
namespace phpjs\elements;

use phpjs\urls\HostFactory;
use phpjs\urls\URLParser;

/**
 * @see https://html.spec.whatwg.org/multipage/semantics.html#htmlhyperlinkelementutils
 */
trait HTMLHyperlinkElementUtils
{
    private $mUrl;

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

        if ($this->mUrl === null) {
            $href = $this->mAttributesList->getAttrByNamespaceAndLocalName(
                null,
                'href'
            );

            if (!$href) {
                return '';
            }

            return $href->value;
        }

        return $this->mUrl->serializeURL();
    }

    /**
     * Sets the Element's href content attribute to the given value.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-href
     *
     * @param string $aValue A URL.
     */
    protected function setHref($aValue)
    {
        $this->mAttributesList->setAttrValue('href', $aValue);
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

        if ($this->mUrl === null) {
            return '';
        }

        return $this->mUrl->getOrigin()->serializeAsUnicode();
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

        if ($this->mUrl === null) {
            return ':';
        }

        return $this->mUrl->scheme . ':';
    }

    /**
     * Sets the Element's URL's protocol and updates the Element's href content
     * attribute.
     *
     * @internal
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-protocol
     *
     * @param string $aValue A URL scheme.
     */
    protected function setProtocol($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null) {
            return;
        }

        URLParser::parseBasicUrl(
            $aValue . ':',
            null,
            null,
            $this->mUrl,
            URLParser::SCHEME_START_STATE
        );
        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null) {
            return '';
        }

        return $this->mUrl->username;
    }

    /**
     * Sets the Element's URL's username and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-username
     *
     * @param string $aValue A username.
     */
    protected function setUsername($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null ||
            $this->mUrl->host === null ||
            $this->mUrl->cannotBeABaseUrl
        ) {
            return;
        }

        $this->mUrl->setUsername($aValue);
        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null) {
            return '';
        }

        return $this->mUrl->password;
    }

    /**
     * Sets the Element's URL's password and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-password
     *
     * @param string $aValue A password.
     */
    protected function setPassword($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null ||
            $this->mUrl->host === null ||
            $this->mUrl->cannotBeABaseUrl
        ) {
            return;
        }

        $this->mUrl->setPassword($aValue);
        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null || $this->mUrl->host === null) {
            return '';
        }

        if ($this->mUrl->port === null) {
            return HostFactory::serialize($this->mUrl->host);
        }

        return HostFactory::serialize($this->mUrl->host) . ':' .
            $this->mUrl->port;
    }

    /**
     * Sets the Element's URL's host and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-host
     *
     * @param string $aValue A host.
     */
    protected function setHost($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null || $this->mUrl->cannotBeABaseUrl) {
            return;
        }

        URLParser::parseBasicUrl(
            $aValue,
            null,
            null,
            $this->mUrl,
            URLParser::HOST_STATE
        );
        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null || $this->mUrl->host === null) {
            return '';
        }

        return HostFactory::serialize($this->mUrl->host);
    }

    /**
     * Sets the Element's URL's hostname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hostname
     *
     * @param string $aValue A hostname.
     */
    protected function setHostname($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null || $this->mUrl->cannotBeABaseUrl) {
            return;
        }

        URLParser::parseBasicUrl(
            $aValue,
            null,
            null,
            $this->mUrl,
            URLParser::HOSTNAME_STATE
        );
        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null || $this->mUrl->port === null) {
            return '';
        }

        return $this->mUrl->port;
    }

    /**
     * Sets the Element's URL's port and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-port
     *
     * @param string $aValue A port.
     */
    protected function setPort($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null ||
            $this->mUrl->host === null ||
            $this->mUrl->cannotBeABaseUrl ||
            $this->mUrl->scheme === 'file'
        ) {
            return;
        }

        if ($aValue === '') {
            $this->mUrl->port = null;
        } else {
            URLParser::parseBasicUrl(
                $aValue,
                null,
                null,
                $this->mUrl,
                URLParser::PORT_STATE
            );
        }

        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null) {
            return '';
        }

        if ($this->mUrl->cannotBeABaseUrl) {
            return $this->mUrl->path[0];
        }

        if (empty($this->mUrl->path)) {
            return '';
        }

        return '/' . implode('/', $this->mUrl->path);
    }

    /**
     * Sets the Element's URL's pathname and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-pathname
     *
     * @param string $aValue A path.
     */
    protected function setPathname($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null || $this->mUrl->cannotBeABaseUrl) {
            return;
        }

        $this->mUrl->path = [];
        URLParser::parseBasicUrl(
            $aValue,
            null,
            null,
            $this->mUrl,
            URLParser::PATH_START_STATE
        );
        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null ||
            $this->mUrl->query === null ||
            $this->mUrl->query === ''
        ) {
            return '';
        }

        return '?' . $this->mUrl->query;
    }

    /**
     * Sets the Element's URL's search and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-search
     *
     * @param string $aValue A URL query.
     */
    protected function setSearch($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null) {
            return;
        }

        if ($aValue === '') {
            $this->mUrl->query = null;
        } else {
            $input = $aValue;

            if (mb_substr($aValue, 0, 1) === '?') {
                $input = mb_substr($aValue, 1);
            }

            $this->mUrl->query = '';
            URLParser::parseBasicUrl(
                $input,
                null,
                $this->nodeDocument->characterSet,
                $this->mUrl,
                URLParser::QUERY_STATE
            );
        }

        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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

        if ($this->mUrl === null ||
            $this->mUrl->fragment === null ||
            $this->mUrl->fragment === ''
        ) {
            return '';
        }

        return '#' . $this->mUrl->fragment;
    }

    /**
     * Sets the Element's URL's hash and updates the Element's href content
     * attribute.
     *
     * @see https://html.spec.whatwg.org/multipage/semantics.html#dom-hyperlink-hash
     *
     * @param string $aValue A URL fragment.
     */
    protected function setHash($aValue)
    {
        $this->reinitialiseUrl();

        if ($this->mUrl === null || $this->mUrl->scheme === 'javascript') {
            return;
        }

        if ($aValue === '') {
            $this->mUrl->fragment = null;
        } else {
            $input = $aValue;

            if (mb_substr($aValue, 0, 1) === '#') {
                $input = mb_substr($aValue, 1);
            }

            $this->mUrl->fragment = '';
            URLParser::parseBasicUrl(
                $input,
                null,
                null,
                $this->mUrl,
                URLParser::FRAGMENT_STATE
            );
        }

        $this->mAttributesList->setAttrValue(
            'href',
            $this->mUrl->serializeURL()
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
        if ($this->mUrl &&
            $this->mUrl->scheme === 'blob' &&
            $this->mUrl->cannotBeABaseUrl
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
        $href = $this->mAttributesList->getAttrByNamespaceAndLocalName(
            null,
            'href'
        );

        // If this element's href content attribute is absent, set this
        // element's url to null.
        if (!$href) {
            $this->mUrl = null;
            return;
        }

        // Otherwise, parse this element's href content attribute value relative
        // to this element's node document.
        $url = $this->parseURL($href->value, $this->nodeDocument);

        // If parsing is successful, set this element's url to the result;
        // otherwise, set this element's url to null.
        $this->mUrl = $url === false ? null : $url['urlRecord'];
    }
}
