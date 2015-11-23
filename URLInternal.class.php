<?php
namespace phpjs\urls;

use phpjs\urls;

require_once 'URLParser.class.php';

class URLInternal {
    const FLAG_ARRAY = 1;
    const FLAG_AT = 2;
    const FLAG_NON_RELATIVE = 4;

    private $mFlags;
    private $mFragment;
    private $mHost;
    private $mPassword;
    private $mPath;
    private $mPort;
    private $mQuery;
    private $mScheme;
    private $mUsername;

    public function __construct() {
        $this->mFlags = 0;
        $this->mFragment = null;
        $this->mHost = null;
        $this->mPassword = null;
        $this->mPath = new \SplDoublyLinkedList();
        $this->mPort = null;
        $this->mQuery = null;
        $this->mScheme = '';
        $this->mUsername = '';
    }

    public static function domainToASCII($aDomain) {
        //$asciiDomain = URLParser::parseHost($aDomain);

        // TODO: Return the empty string if asciiDomain is not a domain, and asciiDomain otherwise.
        return $aDomain;
    }

    public static function domainToUnicode($aDomain) {
        //$unicodeDomain = URLParser::parseHost($aDomain);

        // TODO: Return the empty string if unicodeDomain is not a domain, and unicodeDomain otherwise.
        return $aDomain;
    }

    public function getFragment() {
        return $this->mFragment;
    }

    public function getHost() {
        return $this->mHost;
    }

    public function getPassword() {
        return $this->mPassword;
    }

    public function getPath() {
        return $this->mPath;
    }

    public function getPort() {
        return $this->mPort;
    }

    public function getQuery() {
        return $this->mQuery;
    }

    public function getScheme() {
        return $this->mScheme;
    }

    public function getUsername() {
        return $this->mUsername;
    }

    /**
     * Determines whether two URLs are equal to eachother.
     *
     * @link https://url.spec.whatwg.org/#concept-url-equals
     *
     * @param  URLInternal $aOtherUrl        A URL to compare equality against.
     *
     * @param  bool|null   $aExcludeFragment Optional argument that determines whether a URL's
     *                                       fragment should be factored into equality.
     *
     * @return bool
     */
    public function isEqual(URLInternal $aOtherUrl, $aExcludeFragment = null) {
        return URLParser::serializeURL($this, $aExcludeFragment) == URLParser::serializeURL($aOtherUrl, $aExcludeFragment);
    }

    public function isFlagSet($aFlag) {
        return (bool)($this->mFlags & $aFlag);
    }

    /**
     * Returns whether or not the URL's scheme is a special scheme.
     *
     * @link https://url.spec.whatwg.org/#is-special
     *
     * @return boolean
     */
    public function isSpecial() {
        return array_key_exists($this->mScheme, URLParser::$specialSchemes);
    }

    public function setFlag($aFlag) {
        $this->mFlags |= $aFlag;
    }

    public function setFragment($aFragment) {
        $this->mFragment = $aFragment;
    }

    public function setHost($aHost) {
        $this->mHost = $aHost;
    }

    public function setPassword($aPassword) {
        $this->mPassword = $aPassword;
    }

    /**
     * Set the URL's password and reparses the URL.
     *
     * @link https://url.spec.whatwg.org/#set-the-password
     *
     * @param string $aPassword The URL's password.
     */
    public function setPasswordSteps($aPassword) {
        if ($aPassword === '') {
            $this->mPassword = null;
        } else {
            $this->mPassword = '';

            for ($i = 0; $i < mb_strlen($aPassword); $i++) {
                $this->mPassword .= URLParser::utf8PercentEncode(mb_substr($aPassword, $i, 1), URLParser::ENCODE_SET_USERINFO);
            }
        }
    }

    public function setPath(\SplDoublyLinkedList $aPath) {
        $this->mPath = $aPath;
    }

    public function setPort($aPort) {
        $this->mPort = $aPort;
    }

    public function setQuery($aQuery) {
        $this->mQuery = $aQuery;
    }

    public function setScheme($aScheme) {
        $this->mScheme = $aScheme;
    }

    public function setUsername($aUsername) {
        $this->mUsername = $aUsername;
    }

    /**
     * Sets the URLs username and reparses the URL.
     *
     * @link https://url.spec.whatwg.org/#set-the-username
     *
     * @param string $aUsername The URL's username.
     */
    public function setUsernameSteps($aUsername) {
        $this->mUsername = '';

        for ($i = 0; $i < mb_strlen($aUsername); $i++) {
            $this->mUsername .= URLParser::utf8PercentEncode(mb_substr($aUsername, $i, 1), URLParser::ENCODE_SET_USERINFO);
        }
    }

    public function unsetFlag($aFlag) {
        $this->mFlags &= ~$aFlag;
    }
}
