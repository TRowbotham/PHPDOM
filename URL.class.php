<?php
namespace phpjs\url;

use phpjs\url;

require_once 'Exceptions.class.php';
require_once 'URLParser.class.php';
require_once 'URLSearchParams.class.php';

/**
 * Represents a URL that can be manipulated.
 *
 * @link https://url.spec.whatwg.org/#api
 * @link https://developer.mozilla.org/en-US/docs/Web/API/URL
 */
class URL {
    private $mSearchParams;
    private $mUrl;

	public function __construct($aUrl, $aBase = null) {
        $this->mSearchParams = null;
        $this->mUrl = null;
        $parsedBase = null;

        if ($aBase) {
            $parsedBase = URLParser2::basicURLParser($aBase);

            if ($parsedBase === false) {
                throw new \TypeError($aBase . ' is not a valid URL.');
            }
        }

        $parsedURL = URLParser2::basicURLParser($aUrl, $parsedBase);

        if ($parsedURL === false) {
            throw new \TypeError($aUrl . ' is not a valid URL.');
        }

        $this->mUrl = $parsedURL;
        $this->mSearchParams = new URLSearchParams2($this->mUrl->getQuery());
        $this->mSearchParams->_setUrl($parsedURL);
	}

    public function __get($aName) {
        switch ($aName) {
            case 'hash':
                $fragment = $this->mUrl->getFragment();

                return !$fragment ? '' : '#' . $fragment;

            case 'host':
                $host = $this->mUrl->getHost();
                $port = $this->mUrl->getPort();

                if ($host === null) {
                    return '';
                }

                if ($port === null) {
                    return URLParser2::serializeHost($host);
                }

                return URLParser2::serializeHost($host) . ':' . $port;

            case 'hostname':
                $host = $this->mUrl->getHost();

                return $host === null ? '' : URLParser2::serializeHost($host);

            case 'href':
                return URLParser2::serializeURL($this->mUrl);

            case 'origin':
                // TODO

                return;

            case 'password':
                $password = $this->mUrl->getPassword();

                return $password === null ? '' : $password;

            case 'pathname':
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
                $port = $this->mUrl->getPort();

                return $port === null ? '' : $port;

            case 'protocol':
                return $this->mUrl->getScheme() . ':';

            case 'search':
                $query = $this->mUrl->getQuery();

                return !$query ? '' : '?' . $query;

            case 'searchParams':
                return $this->mSearchParams;

            case 'username':
                return $this->mUrl->getUsername();

        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'hash':
                if ($this->mUrl->getScheme() == 'javascript') {
                    // Terminate these steps
                    return;
                }

                if ($aValue === '') {
                    $this->mUrl->setFragment(null);

                    // Terminate these steps
                    return;
                }

                $input = $aValue[0] == '#' ? substr($aValue, 1) : $aValue;
                $this->mUrl->setFragment('');
                URLParser2::basicURLParser($input, null, null, $this->mUrl, URLParser2::FRAGMENT_STATE);

                break;

            case 'host':
                if ($this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                URLParser2::basicURLParser($aValue, null, null, $this->mUrl, URLParser2::HOST_STATE);

                break;

            case 'hostname':
                if ($this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                URLParser2::basicURLParser($aValue, null, null, $this->mUrl, URLParser2::HOSTNAME_STATE);

            case 'href':
                $parsedURL = URLParser2::basicURLParser($aValue);

                if ($parsedURL === false) {
                    throw new \TypeError($aValue . ' is not a valid URL.');
                }

                $this->mUrl = $parsedURL;
                $this->mSearchParams->_setUrl($parsedURL);

                break;

            case 'password':
                if ($this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setPasswordSteps($aValue);

                break;

            case 'pathname':
                if ($this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                while (!$this->mUrl->getPath()->isEmpty()) {
                    $this->mUrl->getPath()->pop();
                }

                URLParser2::basicURLParser($aValue, null, null, $this->mUrl, URLParser2::PATH_START_STATE);

                break;

            case 'port':
                if ($this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE) || $this->mUrl->getScheme() == 'file') {
                    // Terminate these steps
                    return;
                }

                URLParser2::basicURLParser($aValue, null, null, $this->mUrl, URLParser2::PORT_STATE);

                break;

            case 'protocol':
                URLParser2::basicURLParser($aValue . ':', null, null, $this->mUrl, URLParser2::SCHEME_START_STATE);

                break;

            case 'search':
                $query = $this->mUrl->getQuery();

                if ($aValue === '') {
                    $this->mUrl->setQuery(null);

                    // TODO: Empty the query object
                }

                $input = $aValue[0] == '?' ? substr($aValue, 1) : $aValue;
                $this->mUrl->setQuery('');
                URLParser2::basicURLParser($input, null, null, $this->mUrl, URLParser2::QUERY_STATE);

                // TODO: Set url’s query object’s list to the result of parsing input.

                break;

            case 'username':
                if ($this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setUsernameSteps($aValue);

                break;
        }
    }
}
