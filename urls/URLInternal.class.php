<?php
namespace phpjs\urls;

class URLInternal {
    const SCHEME_START_STATE = 1;
    const SCHEME_STATE = 2;
    const NO_SCHEME_STATE = 3;
    const SPECIAL_RELATIVE_OR_AUTHORITY_STATE = 4;
    const PATH_OR_AUTHORITY_STATE = 5;
    const RELATIVE_STATE = 6;
    const RELATIVE_SLASH_STATE = 7;
    const SPECIAL_AUTHORITY_SLASHES_STATE = 8;
    const SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE = 9;
    const AUTHORITY_STATE = 10;
    const HOST_STATE = 11;
    const HOSTNAME_STATE = 12;
    const PORT_STATE = 13;
    const FILE_STATE = 14;
    const FILE_SLASH_STATE = 15;
    const FILE_HOST_STATE = 16;
    const PATH_START_STATE = 17;
    const PATH_STATE = 18;
    const NON_RELATIVE_PATH_STATE = 19;
    const QUERY_STATE = 20;
    const FRAGMENT_STATE = 21;

    const FLAG_ARRAY = 1;
    const FLAG_AT = 2;
    const FLAG_NON_RELATIVE = 4;

    private static $singleDotPathSegment = array('.' => '', '%2e' => '');
    private static $doubleDotPathSegment = array('..' => '', '.%2e' => '', '%2e.' => '', '%2e%2e' => '');

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

    /**
     * Parses a string as a URL.  The string can be an absolute URL or a relative URL.  If a relative URL is give,
     * a base URL must also be given so that a complete URL can be resolved.  It can also parse individual parts of a URL
     * when the state machine starts in a specific state.
     *
     * @link https://url.spec.whatwg.org/#concept-basic-url-parser
     *
     * @param  string           $aInput    The URL string that is to be parsed.
     *
     * @param  URLInternal|null $aBaseUrl  Optional argument that is only needed if the input is a relative URL.  This represents the base URL,
     *                                     which in most cases, is the document's URL, it may also be a node's base URI or whatever base URL you
     *                                     wish to resolve relative URLs against. Default is null.
     *
     * @param  string           $aEncoding Optional argument that overrides the default encoding.  Default is UTF-8.
     *
     * @param  URLInternal|null $aUrl      Optional argument.  This represents an existing URL object that should be modified based on the input
     *                                     URL and optional base URL.  Default is null.
     *
     * @param  int|null         $aState    Optional argument. An integer that determines what state the state machine will begin parsing the
     *                                     input URL from.  Suppling a value for this parameter will override the default state of SCHEME_START_STATE.
     *                                     Default is null.
     *
     * @return URLInternal|bool            Returns a URL object upon successfully parsing the input or false if parsing input failed.
     */
    public static function basicURLParser($aInput, URLInternal $aBaseUrl = null, $aEncoding = null, URLInternal $aUrl = null, $aState = null) {
        if ($aUrl) {
            $url = $aUrl;
            $input = $aInput;
        } else {
            $url = new URLInternal();
            $input = trim($aInput);
        }

        $state = $aState ? $aState : self::SCHEME_START_STATE;
        $base = $aBaseUrl;
        $encoding = $aEncoding ? $aEncoding : 'utf-8';
        $buffer = '';

        for ($pointer = 0, $len = mb_strlen($input, $encoding); $pointer <= $len; $pointer++) {
            $c = mb_substr($input, $pointer, 1, $encoding);

            switch ($state) {
                case self::SCHEME_START_STATE:
                    if (preg_match(URLUtils::REGEX_ASCII_ALPHA, $c)) {
                        $buffer .= strtolower($c);
                        $state = self::SCHEME_STATE;
                    } elseif (!$aState) {
                        $state = self::NO_SCHEME_STATE;
                        $pointer--;
                    } else {
                        // Syntax violation. Terminate this algorithm.
                        break;
                    }

                    break;

                case self::SCHEME_STATE:
                    if (preg_match(URLUtils::REGEX_ASCII_ALPHANUMERIC, $c) || preg_match('/[+\-.]/', $c)) {
                        $buffer .= strtolower($c);
                    } elseif ($c == ':') {
                        if ($aState) {
                            $bufferIsSpecialScheme = isset(URLUtils::$specialSchemes[$buffer]);
                            $urlIsSpecial = $url->isSpecial();

                            if (($urlIsSpecial && !$bufferIsSpecialScheme) ||
                                (!$urlIsSpecial && $bufferIsSpecialScheme)) {
                                // Terminate this algorithm.
                                break;
                            }
                        }

                        $url->mScheme = $buffer;
                        $buffer = '';

                        if ($aState) {
                            // Terminate this algoritm
                            break;
                        }

                        $offset = $pointer + 1;
                        $urlIsSpecial = $url->isSpecial();

                        if ($url->mScheme == 'file') {
                            if (mb_strpos($input, '//', $offset, $encoding) === $offset) {
                                // Syntax violation
                            }

                            $state = self::FILE_STATE;
                        } elseif ($urlIsSpecial && $base && $base->mScheme == $url->mScheme) {
                            $state = self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE;
                        } elseif ($urlIsSpecial) {
                            $state = self::SPECIAL_AUTHORITY_SLASHES_STATE;
                        } else if (mb_strpos($input, '/', $offset, $encoding) === $offset) {
                            $state = self::PATH_OR_AUTHORITY_STATE;
                        } else {
                            $url->mFlags |= URLInternal::FLAG_NON_RELATIVE;
                            $url->mPath->push('');
                            $state = self::NON_RELATIVE_PATH_STATE;
                        }
                    } elseif (!$aState) {
                        $buffer = '';
                        $state = self::NO_SCHEME_STATE;

                        // Reset the pointer to poing at the first code point.  The pointer needs to be set to -1 to compensate for the
                        // loop incrementing pointer after this iteration.
                        $pointer = -1;
                    } else {
                        // Syntax violation. Terminate this algorithm.
                        break;
                    }

                    break;

                case self::NO_SCHEME_STATE:
                    if (!$base || ($base->mFlags & URLInternal::FLAG_NON_RELATIVE && $c != '#')) {
                        // Syntax violation. Return failure
                        return false;
                    } else if ($base->mFlags & URLInternal::FLAG_NON_RELATIVE && $c == '#') {
                        $url->mScheme = $base->mScheme;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = $base->mQuery;
                        $url->mFragment = '';
                        $url->mFlags |= URLInternal::FLAG_NON_RELATIVE;
                        $state = self::FRAGMENT_STATE;
                    } else if ($base->mScheme != 'file') {
                        $state = self::RELATIVE_STATE;
                        $pointer--;
                    } else {
                        $state = self::FILE_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE:
                    $offset = $pointer + 1;

                    if ($c == '/' && mb_strpos($input, '/', $offset, $encoding) === $offset) {
                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                        $pointer++;
                    } else {
                        // Syntax violation
                        $state = self::RELATIVE_STATE;
                        $pointer--;
                    }

                    break;

                case self::PATH_OR_AUTHORITY_STATE:
                    if ($c == '/') {
                        $state = self::AUTHORITY_STATE;
                    } else {
                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::RELATIVE_STATE:
                    $url->mScheme = $base->mScheme;

                    if ($c === ''/* EOF */) {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = $base->mQuery;
                    } else if ($c == '/') {
                        $state = self::RELATIVE_SLASH_STATE;
                    } else if ($c == '?') {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = '';
                        $state = self::QUERY_STATE;
                    } else if ($c == '#') {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = $base->mQuery;
                        $url->mFragment = '';
                        $state = self::FRAGMENT_STATE;
                    } else {
                        if ($url->isSpecial() && $c == '/') {
                            // Syntax violation
                            $state = self::RELATIVE_SLASH_STATE;
                        } else {
                            $url->mUsername = $base->mUsername;
                            $url->mPassword = $base->mPassword;
                            $url->mHost = $base->mHost;
                            $url->mPort = $base->mPort;
                            $url->mPath = clone $base->mPath;

                            if (!$url->mPath->isEmpty()) {
                                $url->mPath->pop();
                            }

                            $state = self::PATH_STATE;
                            $pointer--;
                        }
                    }

                    break;

                case self::RELATIVE_SLASH_STATE:
                    if ($c == '/' || ($url->isSpecial() && $c == '\\')) {
                        if ($c == '\\') {
                            // Syntax violation
                        }

                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                    } else {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_AUTHORITY_SLASHES_STATE:
                    $offset = $pointer + 1;

                    if ($c == '/' && mb_strpos($input, '/', $offset, $encoding) == $offset) {
                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                        $pointer++;
                    } else {
                        // Syntax violation
                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE:
                    if ($c != '/' && $c != '\\') {
                        $state = self::AUTHORITY_STATE;
                        $pointer--;
                    } else {
                        // Syntax violation
                    }

                    break;

                case self::AUTHORITY_STATE:
                    if ($c == '@') {
                        // Syntax violation

                        if ($url->mFlags & URLInternal::FLAG_AT) {
                            $buffer .= '%40';
                        }

                        $url->mFlags |= URLInternal::FLAG_AT;

                        for ($i = 0, $length = mb_strlen($buffer, $encoding); $i < $length; $i++) {
                            $codePoint = mb_substr($buffer, $i, 1, $encoding);

                            if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $codePoint)) {
                                continue;
                            }

                            if ($codePoint == ':' && $url->mPassword === null) {
                                $url->mPassword = '';
                                continue;
                            }

                            $encodedCodePoints = URLUtils::utf8PercentEncode($codePoint, URLUtils::ENCODE_SET_USERINFO);

                            if ($url->mPassword !== null) {
                                $url->mPassword .= $encodedCodePoints;
                            } else {
                                $url->mUsername .= $encodedCodePoints;
                            }
                        }

                        $buffer = '';
                    } else if (($c === ''/* EOF */ || $c == '/' || $c == '?' || $c == '#') || ($url->isSpecial() && $c == '\\')) {
                        $pointer -= mb_strlen($buffer, $encoding) + 1;
                        $buffer = '';
                        $state = self::HOST_STATE;
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::HOST_STATE:
                case self::HOSTNAME_STATE:
                    if ($c == ':' && !($url->mFlags & URLInternal::FLAG_ARRAY)) {
                        if ($url->isSpecial() && !$buffer) {
                            // Return failure
                            return false;
                        }

                        $host = HostFactory::parse($buffer);

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->mHost = $host;
                        $buffer = '';
                        $state = self::PORT_STATE;

                        if ($aState == self::HOSTNAME_STATE) {
                            // Terminate this algorithm
                            break;
                        }
                    } else if (($c === ''/* EOF */ || $c == '/' || $c == '?' || $c == '#') || ($url->isSpecial() && $c == '\\')) {
                        $pointer--;

                        if ($url->isSpecial() && !$buffer) {
                            // Return failure
                            return false;
                        }

                        $host = HostFactory::parse($buffer);

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->mHost = $host;
                        $buffer = '';
                        $state = self::PATH_START_STATE;

                        if ($aState) {
                            // Terminate this algorithm
                            break;
                        }
                    } else if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c)) {
                        // Syntax violation
                    } else {
                        if ($c == '[') {
                            $url->mFlags |= URLInternal::FLAG_ARRAY;
                        } else if ($c == ']') {
                            $url->mFlags &= ~URLInternal::FLAG_ARRAY;
                        } else {
                            $buffer .= $c;
                        }
                    }

                    break;

                case self::PORT_STATE:
                    if (ctype_digit($c)) {
                        $buffer .= $c;
                    } else if (($c === ''/* EOF */ || $c == '/' || $c == '?' || $c == '#') || ($url->isSpecial() && $c == '\\') || $aState) {
                        if ($buffer) {
                            $port = intval($buffer, 10);

                            if ($port > pow(2, 16) - 1) {
                                // Syntax violation. Return failure.
                                return false;
                            }

                            if (isset(URLUtils::$specialSchemes[$url->mScheme]) && URLUtils::$specialSchemes[$url->mScheme] == $port) {
                                $url->mPort = null;
                            } else {
                                $url->mPort = $port;
                            }

                            $buffer = '';
                        }

                        if ($aState) {
                            // Terminate this algorithm
                            break;
                        }

                        $state = self::PATH_START_STATE;
                        $pointer--;
                    } else if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c)) {
                        // Syntax violation
                    } else {
                        // Syntax violation. Return failure.
                        return false;
                    }

                    break;

                case self::FILE_STATE:
                    if ($url->mScheme == 'file') {
                        if ($c === ''/* EOF */) {
                            if ($base && $base->mScheme == 'file') {
                                $url->mHost = $base->mHost;
                                $url->mPath = clone $base->mPath;
                                $url->mQuery = $base->mQuery;
                            }
                        } else if ($c == '/' || $c == '\\') {
                            if ($c == '\\') {
                                // Syntax violation
                            }

                            $state = self::FILE_SLASH_STATE;
                        } else if ($c == '?') {
                            if ($base && $base->mScheme == 'file') {
                                $url->mHost = $base->mHost;
                                $url->mPath = clone $base->mPath;
                                $url->mQuery = '';
                                $state = self::QUERY_STATE;
                            }
                        } else if ($c == '#') {
                            if ($base && $base->mScheme == 'file') {
                                $url->mHost = $base->mHost;
                                $url->mPath = clone $base->mPath;
                                $url->mQuery = $base->mQuery;
                                $url->mFragment = $base->mFragment;
                                $state = self::FRAGMENT_STATE;
                            }
                        } else {
                            // Platform-independent Windows drive letter quirk
                            if ($base && $base->mScheme == 'file' && (
                                !preg_match(URLUtils::REGEX_WINDOWS_DRIVE_LETTER, mb_substr($input, $pointer, 2, $encoding)) ||
                                mb_strlen(mb_substr($input, $pointer, mb_strlen($input, $encoding), $encoding), $encoding) == 1 ||
                                !preg_match('/[/\\?#]/', mb_substr($input, $pointer + 2, 1, $encoding)))) {
                                $url->mHost = $base->mHost;
                                $url->mPath = clone $base->mPath;
                                self::popURLPath($url);
                            } else if ($base && $base->mScheme == 'file') {
                                // Syntax violation
                            } else {
                                $state = self::PATH_STATE;
                                $pointer--;
                            }
                        }
                    }

                    break;

                case self::FILE_SLASH_STATE:
                    if ($c == '/' || $c == '\\') {
                        if ($c == '\\') {
                            // Syntax violation
                        }

                        $state = self::FILE_HOST_STATE;
                    } else {
                        if ($base && $base->mScheme == 'file' && preg_match(URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER, $base->mPath[0])) {
                            // This is a (platform-independent) Windows drive letter quirk. Both url’s and base’s
                            // host are null under these conditions and therefore not copied.
                            $url->mPath->push($base->mPath[0]);
                        }

                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::FILE_HOST_STATE:
                    if ($c === ''/* EOF */ || $c == '/' || $c == '\\' || $c == '?' || $c == '#') {
                        $pointer--;


                        if (preg_match(URLUtils::REGEX_WINDOWS_DRIVE_LETTER, $buffer)) {
                            // This is a (platform-independent) Windows drive letter quirk. buffer is not reset here and instead used in the path state.
                            // Syntax violation
                            $state = self::PATH_STATE;
                        } else if (!$buffer) {
                            $state = self::PATH_START_STATE;
                        } else {
                            $host = HostFactory::parse($buffer);

                            if ($host === false) {
                                // Return failure
                                return false;
                            }

                            if ($host != 'localhost') {
                                $url->mHost = $host;
                            }

                            $buffer = '';
                            $state = self::PATH_START_STATE;
                        }
                    } else if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c)) {
                        // Syntax violation
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::PATH_START_STATE:
                    $urlIsSpecial = $url->isSpecial();

                    if ($urlIsSpecial && $c == '\\') {
                        // Syntax violation
                    }

                    $state = self::PATH_STATE;

                    if ($c != '/' && !($urlIsSpecial && $c == '\\')) {
                        $pointer--;
                    }

                    break;

                case self::PATH_STATE:
                    if ($c === ''/* EOF */ || $c == '/' || ($url->isSpecial() && $c == '\\') || (!$aState && ($c == '?' || $c == '#'))) {
                        $urlIsSpecial = $url->isSpecial();

                        if ($urlIsSpecial && $c == '\\') {
                            // Syntax violation
                        }

                        if (isset(self::$doubleDotPathSegment[$buffer])) {
                            self::popURLPath($url);

                            if ($c != '/' && !($urlIsSpecial && $c == '\\')) {
                                $url->mPath->push('');
                            }
                        } else if (isset(self::$singleDotPathSegment[$buffer]) && $c != '/' && !($url->isSpecial() && $c == '\\')) {
                            $url->mPath->push('');
                        } else if (!isset(self::$singleDotPathSegment[$buffer])) {
                            if ($url->mScheme == 'file' && $url->mPath->isEmpty() && preg_match(URLUtils::REGEX_WINDOWS_DRIVE_LETTER, $buffer)) {
                                if ($url->mHost !== null) {
                                    // Syntax violation
                                }

                                $url->mHost = null;
                                // This is a (platform-independent) Windows drive letter quirk.
                                $buffer = mb_substr($buffer, 0, 1, $encoding) . ':';
                            }

                            $url->mPath->push($buffer);
                        }

                        $buffer = '';

                        if ($c == '?') {
                            $url->mQuery = '';
                            $state = self::QUERY_STATE;
                        } else if ($c == '#') {
                            $url->mFragment = '';
                            $state = self::FRAGMENT_STATE;
                        }
                    } else if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c)) {
                        // Syntax violation
                    } else {
                        if (!preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !ctype_xdigit(mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        $buffer .= URLUtils::utf8PercentEncode($c, URLUtils::ENCODE_SET_DEFAULT);
                    }

                    break;

                case self::NON_RELATIVE_PATH_STATE:
                    if ($c == '?') {
                        $url->mQuery = '';
                        $state = self::QUERY_STATE;
                    } else if ($c == '#') {
                        $url->mFragment = '';
                        $state = self::FRAGMENT_STATE;
                    } else {
                        if ($c !== ''/* EOF */ && !preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !ctype_xdigit(mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        if ($c !== ''/* EOF */ && !preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c)) {
                            if (!$url->mPath->isEmpty()) {
                                $url->mPath[0] .= URLUtils::utf8PercentEncode($c);
                            }
                        }
                    }

                    break;

                case self::QUERY_STATE:
                    if ($c === ''/* EOF */ || (!$aState && $c == '#')) {
                        if (!$url->isSpecial() || $url->mScheme == 'ws' || $url->mScheme == 'wss') {
                            $encoding = 'utf-8';
                        }

                        $buffer = mb_convert_encoding($buffer, $encoding);

                        for ($i = 0, $length = strlen($buffer); $i < $length; $i++) {
                            $byteOrd = ord($buffer[$i]);

                            if ($byteOrd < 0x21 || $byteOrd > 0x7E || $byteOrd == 0x22 || $byteOrd == 0x23 || $byteOrd == 0x3C || $byteOrd == 0x3E) {
                                $url->mQuery .= URLUtils::percentEncode($buffer[$i]);
                            } else {
                                $url->mQuery .= $buffer[$i];
                            }
                        }

                        $buffer = '';

                        if ($c == '#') {
                            $url->mFragment = '';
                            $state = self::FRAGMENT_STATE;
                        }
                    } else if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c)) {
                        // Syntax violation
                    } else {
                        if (!preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !ctype_xdigit(mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        $buffer .= $c;
                    }

                    break;

                case self::FRAGMENT_STATE:
                    if ($c === ''/* EOF */) {
                        // Do nothing
                    } else if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c) || preg_match('/\x{0000}/', $c)) {
                        // Syntax violation
                    } else {
                        if (!preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !ctype_xdigit(mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        $url->mFragment .= $c;
                    }

                    break;
            }
        }

        return $url;
    }

    public static function domainToASCII($aDomain) {
        //$asciiDomain = HostFactory::parse($aDomain);

        // TODO: Return the empty string if asciiDomain is not a domain, and asciiDomain otherwise.
        return $aDomain;
    }

    public static function domainToUnicode($aDomain) {
        //$unicodeDomain = HostFactory::parse($aDomain);

        // TODO: Return the empty string if unicodeDomain is not a domain, and unicodeDomain otherwise.
        return $aDomain;
    }

    public function getFragment() {
        return $this->mFragment;
    }

    public function getHost() {
        return $this->mHost;
    }

    /**
     * Computes a URL's origin.
     *
     * @link https://url.spec.whatwg.org/#origin
     *
     * @return array
     */
    public function getOrigin() {
        switch ($this->mScheme) {
            case 'blob':
                $url = self::basicURLParser($this->mPath[0]);

                if ($url === false) {
                    // TODO: Return globally unique identifier.
                    return;
                }

                return $url->getOrigin();

            case 'ftp':
            case 'gopher':
            case 'http':
            case 'https':
            case 'ws':
            case 'wss':
                return array(
                            'scheme' => $this->mScheme,
                            'host' => $this->mHost,
                            'port' => ($this->mPort === null ? URLUtils::$specialSchemes[$this->mScheme] : $this->mPort)
                        );

                break;

            case 'file':
                // TODO: Unfortunate as it is, this is left as an exercise to the reader. When in doubt, return a new globally unique identifier.
                return array('scheme' => $this->mScheme, 'host' => '', 'port' => '');

            default:
                // TODO: Return a new globally unique identifier.
                return;
        }
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
        return $this->serializeURL($aExcludeFragment) == $aOtherUrl->serializeURL($aExcludeFragment);
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
        return isset(URLUtils::$specialSchemes[$this->mScheme]);
    }

    /**
     * Serializes an origin using Unicode.
     *
     * @link https://html.spec.whatwg.org/multipage/browsers.html#unicode-serialisation-of-an-origin
     *
     * @param  array    $aOrigin An origin.
     *
     * @return string
     */
    public static function serializeOriginAsUnicode($aOrigin) {
        if (!is_array($aOrigin)) {
            return 'null';
        }

        $result = $aOrigin['scheme'];
        $result .= '://';

        $hostParts = explode('.', HostFactory::parse($aOrigin['host']));
        $result .= implode('.', array_map(array('self', 'domainToUnicode'), $hostParts));

        if ($aOrigin['port'] != URLUtils::$specialSchemes[$aOrigin['scheme']]) {
            $result .= ':' . intval($aOrigin['port'], 10);
        }

        return $result;
    }

    /**
     * Serializes a URL object.
     *
     * @link https://url.spec.whatwg.org/#concept-url-serializer
     *
     * @param  bool|null    $aExcludeFragment Optional argument, that, when specified will exclude the URL's
     *                                        fragment from being serialized.
     * @return string
     */
    public function serializeURL($aExcludeFragment = null) {
        $output = $this->mScheme . ':';

        if ($this->mHost !== null) {
            $output .= '//';

            if ($this->mUsername !== '' || $this->mPassword !== null) {
                $output .= $this->mUsername;

                if ($this->mPassword !== null) {
                    $output .= ':' . $this->mPassword;
                }

                $output .= '@';
            }

            $output .= HostFactory::serialize($this->mHost);

            if ($this->mPort !== null) {
                $output .= ':' . $this->mPort;
            }
        } else if ($this->mHost === null && $this->mScheme == 'file') {
            $output .= '//';
        }

        if ($this->mFlags & URLInternal::FLAG_NON_RELATIVE) {
            $output .= $this->mPath[0];
        } else {
            $output .= '/';

            foreach ($this->mPath as $key => $path) {
                if ($key > 0) {
                    $output .= '/';
                }

                $output .= $path;
            }
        }

        if ($this->mQuery !== null) {
            $output .= '?' . $this->mQuery;
        }

        if (!$aExcludeFragment && $this->mFragment !== null) {
            $output .= '#' . $this->mFragment;
        }

        return $output;
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

            for ($i = 0, $len = mb_strlen($aPassword); $i < $len; $i++) {
                $this->mPassword .= URLUtils::utf8PercentEncode(mb_substr($aPassword, $i, 1), URLUtils::ENCODE_SET_USERINFO);
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

        for ($i = 0, $len = mb_strlen($aPassword); $i < $len; $i++) {
            $this->mUsername .= URLUtils::utf8PercentEncode(mb_substr($aUsername, $i, 1), URLUtils::ENCODE_SET_USERINFO);
        }
    }

    public function unsetFlag($aFlag) {
        $this->mFlags &= ~$aFlag;
    }

    /**
     * Parses a URL.
     *
     * @link https://url.spec.whatwg.org/#concept-url-parser
     *
     * @param string            $aInput    The URL string to be parsed.
     *
     * @param URLInternal|null  $aBase     A base URL to resolve relative URLs against.
     *
     * @param string            $aEncoding The character encoding of the URL.
     *
     * @return URLInternal|bool
     */
    public static function URLParser($aInput, URLInternal $aBase = null, $aEncoding = null) {
        $url = self::basicURLParser($aInput, $aBase, $aEncoding);

        if ($url === false) {
            return false;
        }

        if ($url->mScheme != 'blob') {
            return $url;
        }

        // TODO: If the first string in url’s path is not in the blob URL store, return url
        // TODO: Set url’s object to a structured clone of the entry in the blob URL store corresponding to the first string in url’s path

        return $url;
    }

    /**
     * Removes the last string from a URL's path if its scheme is not "file"
     * and the path does not contain a normalized Windows drive letter.
     *
     * @link https://url.spec.whatwg.org/#pop-a-urls-path
     *
     * @param  URLInternal $aUrl The URL of the path that is to be popped.
     */
    protected static function popURLPath(URLInternal $aUrl) {
        if (!$aUrl->mPath->isEmpty()) {
            $containsDriveLetter = false;

            foreach ($aUrl->mPath as $path) {
                if (preg_match(URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER, $path)) {
                    $containsDriveLetter = true;
                    break;
                }
            }

            if ($aUrl->mScheme != 'file' || !$containsDriveLetter) {
                $aUrl->mPath->pop();
            }
        }
    }
}
