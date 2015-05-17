<?php
// https://developer.mozilla.org/en-US/docs/Web/API/URLUtilsReadOnly
// https://url.spec.whatwg.org/#urlutilsreadonly

require_once 'URLParser.class.php';
require_once 'URLSearchParams.class.php';

trait URLUtilsReadOnly {
    protected $mInput;
    protected $mSearchParams;
    protected $mUrl;

    private function initURLUtilsReadOnly() {
        $this->mInput = '';
        $this->mSearchParams = null;
        $this->mUrl = null;
    }

    private function URLUtilsGetter($aName) {
        switch ($aName) {
            case 'hash':
                return !$this->mUrl || !$this->mUrl->mFragment ? '' : '#' . $this->mUrl->mFragment;

            case 'host':
                if (!$this->mUrl) {
                    return '';
                }

                return URLParser::serializeHost($this->mUrl->mHost) . ($this->mUrl->mPort ? ':' . $this->mUrl->mPort : '');

            case 'hostname':
                return $this->mUrl ? URLParser::serializeHost($this->mUrl->mHost) : '';

            case 'href':
                return $this->mUrl ? URLParser::serializeURL($this->mUrl) : $this->mInput;

            case 'origin':
                if (!$this->mUrl) {
                    return '';
                }

                $tuple = $this->getOriginTuple();
                $parts = explode(', ', substr($tuple, 1, strlen($tuple) - 2));

                if (count($parts) < 3) {
                    return 'null';
                }

                $result = $parts[0];
                $result .= '://';
                $result .= URL::domainToUnicode($parts[1]);
                $result .= $parts[2] == URLParser::$relativeSchemes[$parts[0]] ? '' : ':' . $parts[2];

                return $result;

            case 'password':
                return !$this->mUrl || !$this->mUrl->mPassword ? '' : $this->mUrl->mPassword;

            case 'pathname':
                if (!$this->mUrl) {
                    return '';
                }

                if ($this->mUrl->mFlags & URL::FLAG_RELATIVE) {
                    $output = '/';

                    foreach ($this->mUrl->mPath as $key => $path) {
                        if ($key > 0) {
                            $output .= '/';
                        }

                        $output .= $path;
                    }
                } else {
                    $output = $this->mUrl->mSchemeData;
                }

                return $output;

            case 'port':
                return !$this->mUrl ? '' : $this->mUrl->mPort;

            case 'protocol':
                return (!$this->mUrl ? '' : $this->mUrl->mScheme) . ':';

            case 'search':
                return $this->mUrl ? '?' . $this->mUrl->mQuery : '';

            case 'searchParams':
                return $this->mSearchParams;

            case 'username':
                return $this->mUrl ? $this->mUrl->mUsername : '';

            default:
                return false;
        }
    }

    private function getOriginTuple() {
        $scheme = null;
        $host = null;
        $port = null;

        switch ($this->mScheme) {
            case 'blob':
                $url = URLParser::basicURLParser($this->mUrl->mSchemeData);

                if ($url === false) {
                    return;
                }

                $scheme = $url->mScheme;
                $host = $url->mHost;
                $port = $url->mPort ? $url->mPort : URLParser::$relativeSchemes[$url->mScheme];

                break;

            case 'ftp':
            case 'gopher':
            case 'http':
            case 'https':
            case 'ws':
            case 'wss':
                $scheme = $this->mUrl->mScheme;
                $host = $this->mUrl->mHost;
                $port = $this->mUrl->mPort ? $this->mUrl->mPort : URLParser::$relativeSchemes[$this->mUrl->mScheme];

                break;

            case 'file':
                // Do something

            default:
                $scheme = $this->mUrl->mScheme;
                $host = $this->mUrl->mHost;
                $port = $this->mUrl->mPort ? $this->mUrl->mPort : URLParser::$relativeSchemes[$this->mUrl->mScheme];
        }

        $tuple = '(';

        if ($scheme) {
            $tuple .= $scheme;
        }

        if ($host) {
            if ($scheme) {
                $tuple .= ', ';
            }

            $tuple .= $host;
        }

        if ($port) {
            if ($host || $scheme) {
                $tuple .= ', ';
            }

            $tuple .= $port;
        }

        $tuple .= ')';

        return $tuple;
    }

    private function setURLInput($aInput = null, URL $aUrl = null) {
        if ($aUrl) {
            $this->mUrl = $aUrl;
            $this->mInput = $aInput;
        } else {
            $this->mUrl = null;

            if ($aInput === null) {
                $this->mInput = '';
            } else {
                $this->mInput = $aInput;
                $url = URLParser::URLParser($aInput, $this->getBaseURL(), null);

                if ($url) {
                    $this->mUrl = $url;
                }
            }
        }

        $query = $this->mUrl && $this->mUrl->mQuery ? $this->mUrl->mQuery : '';

        if (!$this->mSearchParams) {
            $this->mSearchParams = new URLSearchParams($query);
            $this->mSearchParams->attach($this);
        } else {
            $pairs = URLParser::urlencodedStringParser($query);
            $this->mSearchParams->_mutateList($pairs);
        }
    }
}
