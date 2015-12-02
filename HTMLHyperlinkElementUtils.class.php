<?php
require_once 'urls/URLInternal.class.php';
require_once 'urls/URLUtils.class.php';

use phpjs\urls\URLInternal;
use phpjs\urls\URLUtils;

/**
 * @link https://html.spec.whatwg.org/multipage/semantics.html#htmlhyperlinkelementutils
 */
trait HTMLHyperlinkElementUtils {
    protected $mUrl;

    private function HTMLHyperlinkElementUtilsGetter($aName) {
        switch ($aName) {
            case 'hash':
                $this->reinitialiseURL();

                if ($this->mUrl === null || !($fragment = $this->mUrl->getFragment())) {
                    return '';
                }

                return '#' . $fragment;

            case 'host':
                $this->reinitialiseURL();

                if ($this->mUrl === null || ($host = $this->mUrl->getHost()) === null) {
                    return '';
                }

                if (($port = $this->mUrl->getPort()) === null) {
                    return URLUtils::serializeHost($host);
                }

                return URLUtils::serializeHost($host) . ':' . $port;

            case 'hostname':
                $this->reinitialiseURL();

                if ($this->mUrl === null || ($host = $this->mUrl->getHost()) === null) {
                    return '';
                }

                return URLUtils::serializeHost($host);

            case 'href':
                $this->reinitialiseURL();

                if ($this->mUrl === null && !$this->hasAttribute('href')) {
                    return '';
                } else if ($this->mUrl === null) {
                    return $this->getAttribute('href');
                }

                return URLUtils::serializeURL($this->mUrl);

            case 'origin':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    return '';
                }

                return URLUtils::serializeOriginAsUnicode($this->mUrl->getOrigin());

            case 'password':
                $this->reinitialiseURL();

                if ($this->mUrl === null || ($password = $this->mUrl->getPassword()) === null) {
                    return '';
                }

                return $password;

            case 'pathname':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    return '';
                }

                if ($this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    return $this->mUrl->getPath()[0];
                }

                $output = '/';

                foreach ($this->mUrl->getPath() as $key => $path) {
                    if ($key > 0) {
                        $output .= '/';
                    }

                    $output .= $path;
                }

                return $output;

            case 'port':
                $this->reinitialiseURL();

                if ($this->mUrl === null || ($port = $this->mUrl->getPort()) === null) {
                    return '';
                }

                return $port;

            case 'protocol':
                $this->reinitialiseURL();

                return $this->mUrl === null ? ':' : $this->mUrl->getScheme() . ':';

            case 'search':
                $this->reinitialiseURL();

                return $this->mUrl === null || !($query = $this->mUrl->getQuery()) ? '' : '?' . $query;

            case 'username':
                $this->reinitialiseURL();

                return $this->mUrl === null ? '' : $this->mUrl->getUsername();

            default:
                return 'HTMLHyperlinkElementUtilsGetter';
        }
    }

    private function HTMLHyperlinkElementUtilsSetter($aName, $aValue) {
        switch ($aName) {
            case 'hash':
                $this->reinitialiseURL();

                if ($this->mUrl === null || !$this->mUrl->getScheme() == 'javascript') {
                    // Terminate these steps
                    return;
                }

                if ($aValue === '') {
                    $this->mUrl->mUrl->setFragment(null);
                } else {
                    $input = $aValue[0] == '#' ? substr($aValue, 1) : $aValue;
                    $this->mUrl->setFragment('');
                    URLInternal::basicURLParser($input, null, null, $this->mUrl, URLInternal::FRAGMENT_STATE);
                }

                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'host':
                $this->reinitialiseURL();

                if ($this->mUrl === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::HOST_STATE);
                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'hostname':
                $this->reinitialiseURL();

                if ($this->mUrl === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::HOSTNAME_STATE);
                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'href':
                $this->_setAttributeValue('href', $aValue);

                break;

            case 'password':
                $this->reinitialiseURL();

                if ($this->mUrl === null || $this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setPasswordSteps($aValue);
                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'pathname':
                $this->reinitialiseURL();

                if ($this->mUrl === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                while (!$this->mUrl->getPath()->isEmpty()) {
                    $this->mUrl->getPath()->pop();
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::PATH_START_STATE);
                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'port':
                $this->reinitialiseURL();

                if ($this->mUrl === null || $this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE) ||
                    $this->mUrl->getScheme() == 'file') {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::PORT_STATE);
                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'protocol':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue . ':', null, null, $this->mUrl, URLInternal::SCHEME_START_STATE);
                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'search':
                $this->reinitialiseURL();

                if ($this->mUrl === null) {
                    // Terminate these steps
                    return;
                }

                if ($aValue === '') {
                    $this->mUrl->setQuery(null);
                } else {
                    $input = $aValue[0] == '?' ? substr($aValue, 1) : $aValue;
                    $this->mUrl->setQuery('');
                    URLInternal::basicURLParser($input, null, $this->mOwnerDocument->characterSet, $this->mUrl, URLInternal::QUERY_STATE);
                }

                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;

            case 'username':
                $this->reinitialiseURL();

                if ($this->mUrl === null || $this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setUsernameSteps($aValue);
                $this->_setAttributeValue('href', URLUtils::serializeURL($this->mUrl));

                break;
        }
    }

    private function initHTMLHyperlinkElementUtils() {
        $this->mUrl = null;
    }

    /**
     * Reintialises the element's URL.
     *
     * @internal
     *
     * @link https://html.spec.whatwg.org/multipage/semantics.html#reinitialise-url
     * @link https://html.spec.whatwg.org/multipage/semantics.html#concept-hyperlink-url-set
     */
    private function reinitialiseUrl() {
        if ($this->mUrl === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
            // Terminate these steps
            return;
        }

        $resolvedURL = $this->resolveURL($this->getAttribute('href'));
        $this->mUrl = $resolvedURL === false ? null : $resolvedURL['parsed_url'];
    }
}
