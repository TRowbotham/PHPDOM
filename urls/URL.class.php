<?php
namespace phpjs\urls;

use phpjs\exceptions\TypeError;

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
            $parsedBase = URLInternal::basicURLParser($aBase);

            if ($parsedBase === false) {
                throw new TypeError($aBase . ' is not a valid URL.');
            }
        }

        $parsedURL = URLInternal::basicURLParser($aUrl, $parsedBase);

        if ($parsedURL === false) {
            throw new TypeError($aUrl . ' is not a valid URL.');
        }

        $this->mUrl = $parsedURL;
        $this->mSearchParams = new URLSearchParams($this->mUrl->getQuery());
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
                    return HostFactory::serialize($host);
                }

                return HostFactory::serialize($host) . ':' . $port;

            case 'hostname':
                $host = $this->mUrl->getHost();

                return $host === null ? '' : HostFactory::serialize($host);

            case 'href':
                return $this->mUrl->serializeURL();

            case 'origin':
                return URLInternal::serializeOriginAsUnicode($this->mUrl->getOrigin());

            case 'password':
                $password = $this->mUrl->getPassword();

                return $password === null ? '' : $password;

            case 'pathname':
                if ($this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL)) {
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
                URLInternal::basicURLParser($input, null, null, $this->mUrl, URLInternal::FRAGMENT_STATE);

                break;

            case 'host':
                if ($this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL)) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::HOST_STATE);

                break;

            case 'hostname':
                if ($this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL)) {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::HOSTNAME_STATE);

                break;

            case 'href':
                $parsedURL = URLInternal::basicURLParser($aValue);

                if ($parsedURL === false) {
                    throw new TypeError($aValue . ' is not a valid URL.');
                }

                $this->mUrl = $parsedURL;
                $this->mSearchParams->_setUrl($parsedURL);

                break;

            case 'password':
                if ($this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL)) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setPasswordSteps($aValue);

                break;

            case 'pathname':
                if ($this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL)) {
                    // Terminate these steps
                    return;
                }

                while (!$this->mUrl->getPath()->isEmpty()) {
                    $this->mUrl->getPath()->pop();
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::PATH_START_STATE);

                break;

            case 'port':
                if ($this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL) || $this->mUrl->getScheme() == 'file') {
                    // Terminate these steps
                    return;
                }

                URLInternal::basicURLParser($aValue, null, null, $this->mUrl, URLInternal::PORT_STATE);

                break;

            case 'protocol':
                URLInternal::basicURLParser($aValue . ':', null, null, $this->mUrl, URLInternal::SCHEME_START_STATE);

                break;

            case 'search':
                $query = $this->mUrl->getQuery();

                if ($aValue === '') {
                    $this->mUrl->setQuery(null);
                    $this->mSearchParams->_mutateList(null);
                }

                $input = $aValue[0] == '?' ? substr($aValue, 1) : $aValue;
                $this->mUrl->setQuery('');
                URLInternal::basicURLParser($input, null, null, $this->mUrl, URLInternal::QUERY_STATE);
                $this->mSearchParams->_mutateList(URLUtils::urlencodedStringParser($input));

                break;

            case 'username':
                if ($this->mUrl->getHost() === null || $this->mUrl->isFlagSet(URLInternal::FLAG_CANNOT_BE_A_BASE_URL)) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setUsernameSteps($aValue);

                break;
        }
    }
}
