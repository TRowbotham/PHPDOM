<?php
// https://developer.mozilla.org/en-US/docs/Web/API/URL
// https://url.spec.whatwg.org/#api

require_once 'Exceptions.class.php';
require_once 'URLUtils.class.php';

class URL extends URLUtils {
    protected $mBase;
    protected $mFlags;
    protected $mFragment;
    protected $mHost;
    protected $mObject;
    protected $mPassword;
    protected $mPath;
    protected $mPort;
    protected $mQuery;
    protected $mScheme;
    protected $mSchemeData;
    protected $mUsername;

	public function __construct() {
		parent::__construct();

        $this->mBase = null;
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
                    throw new TypeError('Error parsing URL.');
                }

                $parsedBase->mUrl = $parsedBase;
            }
        }

        if ($numArgs > 0) {
            $parsedURL = URLParser::basicURLParser($args[0], $parsedBase, null, $this);

            if ($parsedURL === false) {
                throw new TypeError('Error parsing URL.');
            }

            $parsedURL->mUrl = $parsedURL;

            if ($parsedBase) {
                $parsedURL->mBase = $parsedBase;
            }
        }
	}

    public static function domainToASCII($aDomain) {
        $result = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $aDomain);

        return $result;
    }

    public static function domainToUnicode($aDomain) {
        return $aDomain;
    }
}
