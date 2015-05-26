<?php
// https://developer.mozilla.org/en-US/docs/Web/API/URL
// https://url.spec.whatwg.org/#api

require_once 'Exceptions.class.php';
require_once 'URLUtils.class.php';

class URL implements SplObserver {
    use URLUtils;

    const FLAG_ARRAY = 1;
    const FLAG_AT = 2;
    const FLAG_RELATIVE = 4;

    public $mFlags;
    public $mFragment;
    public $mHost;
    public $mObject;
    public $mPassword;
    public $mPath;
    public $mPort;
    public $mQuery;
    public $mScheme;
    public $mSchemeData;
    public $mUsername;

	public function __construct() {
        $this->initURLUtils();
        $this->mFlags = 0;
        $this->mFragment = null;
        $this->mHost = null;
        $this->mObject = null; // Something about supporting Blobs
        $this->mPassword = null;
        $this->mPath = new SplDoublyLinkedList();
        $this->mPort = '';
        $this->mQuery = null;
        $this->mScheme = '';
        $this->mSchemeData = '';
        $this->mUsername = '';

        $args = func_get_args();
        $numArgs = func_num_args();
        $parsedBase = null;

        if ($numArgs > 1) {
            if ($args[1]) {
                $parsedBase = URLParser::basicURLParser($args[1]);

                if ($parsedBase === false) {
                    throw new TypeError($args[1] . ' is not a valid URL.');
                }

                $parsedBase->mUrl = $parsedBase;
            }
        }

        if ($numArgs > 0) {
            $parsedURL = URLParser::basicURLParser($args[0], $parsedBase, null, $this);

            if ($parsedURL === false) {
                throw new TypeError($args[0] . ' is not a valid URL.');
            }

            $this->setURLInput('', $parsedURL);
        }
	}

    public function __get($aName) {
        return $this->URLUtilsGetter($aName);
    }

    public function __set($aName, $aValue) {
        $this->URLUtilsSetter($aName, $aValue);
    }

    public static function domainToASCII($aDomain) {
        $result = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $aDomain);

        return $result;
    }

    public static function domainToUnicode($aDomain) {
        return $aDomain;
    }

    public function update(SplSubject $aObject) {
        if ($aObject instanceof URLSearchParams) {
            $this->mQuery = $aObject->toString();
            $this->preupdate();
        }
    }

    private function getBaseURL() {
        $ssl = isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == 'on';
        $port = in_array($_SERVER['SERVER_PORT'], array(80, 443)) ? '' : ':' . $_SERVER['SERVER_PORT'];
        $url = ($ssl ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];

        return URLParser::basicURLParser($url);
    }

    private function updateURL($aValue) {

    }
}
