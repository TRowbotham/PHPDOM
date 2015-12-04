<?php
namespace phpjs\urls;

require_once 'URLUtils.class.php';

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

    private static $singleDotPathSegment = array('.', '%2e');
    private static $doubleDotPathSegment = array('..', '.%2e', '%2e.', '%2e%2e');

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

        for ($pointer = 0; $pointer <= mb_strlen($input, $encoding); $pointer++) {
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
                            $bufferIsSpecialScheme = false;

                            foreach (URLUtils::$specialSchemes as $scheme => $port) {
                                if (strpos($scheme, $buffer) === 0) {
                                    $bufferIsSpecialScheme = true;
                                    break;
                                }
                            }

                            if (($url->isSpecial() && !$bufferIsSpecialScheme) ||
                                (!$url->isSpecial() && $bufferIsSpecialScheme)) {
                                // Terminate this algorithm.
                                break;
                            }
                        }

                        $url->setScheme($buffer);
                        $buffer = '';

                        if ($aState) {
                            // Terminate this algoritm
                            break;
                        }

                        $offset = $pointer + 1;

                        if ($url->getScheme() == 'file') {
                            if (mb_strpos($input, '//', $offset, $encoding) == $offset) {
                                // Syntax violation
                            }

                            $state = self::FILE_STATE;
                        } elseif ($url->isSpecial() && $base && $base->getScheme() == $url->getScheme()) {
                            $state = self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE;
                        } elseif ($url->isSpecial()) {
                            $state = self::SPECIAL_AUTHORITY_SLASHES_STATE;
                        } else if (mb_strpos($input, '/', $offset, $encoding) == $offset) {
                            $state = self::PATH_OR_AUTHORITY_STATE;
                        } else {
                            $url->setFlag(URLInternal::FLAG_NON_RELATIVE);
                            $url->getPath()->push('');
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
                    if (!$base || ($base->isFlagSet(URLInternal::FLAG_NON_RELATIVE) && $c != '#')) {
                        // Syntax violation. Return failure
                        return false;
                    } else if ($base->isFlagSet(URLInternal::FLAG_NON_RELATIVE) && $c == '#') {
                        $url->setScheme($base->getScheme());
                        $url->setPath(clone $base->getPath());
                        $url->setQuery($base->getQuery());
                        $url->setFragment('');
                        $url->setFlag(URLInternal::FLAG_NON_RELATIVE);
                        $state = self::FRAGMENT_STATE;
                    } else if ($base->getScheme() != 'file') {
                        $state = self::RELATIVE_STATE;
                        $pointer--;
                    } else {
                        $state = self::FILE_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE:
                    $offset = $pointer + 1;

                    if ($c == '/' && mb_strpos($input, '/', $offset, $encoding) == $offset) {
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
                    $url->setScheme($base->getScheme());

                    if ($c === ''/* EOF */) {
                        $url->setUsername($base->getUsername());
                        $url->setPassword($base->getPassword());
                        $url->setHost($base->getHost());
                        $url->setPort($base->getPort());
                        $url->setPath(clone $base->getPath());
                        $url->setQuery($base->getQuery());
                    } else if ($c == '/') {
                        $state = self::RELATIVE_SLASH_STATE;
                    } else if ($c == '?') {
                        $url->setUsername($base->getUsername());
                        $url->setPassword($base->getPassword());
                        $url->setHost($base->getHost());
                        $url->setPort($base->getPort());
                        $url->setPath(clone $base->getPath());
                        $url->setQuery('');
                        $state = self::QUERY_STATE;
                    } else if ($c == '#') {
                        $url->setUsername($base->getUsername());
                        $url->setPassword($base->getPassword());
                        $url->setHost($base->getHost());
                        $url->setPort($base->getPort());
                        $url->setPath(clone $base->getPath());
                        $url->setQuery($base->getQuery());
                        $url->setFragment('');
                    } else {
                        if ($url->isSpecial() && $c == '/') {
                            // Syntax violation
                            $state = self::RELATIVE_SLASH_STATE;
                        } else {
                            $url->setUsername($base->getUsername());
                            $url->setPassword($base->getPassword());
                            $url->setHost($base->getHost());
                            $url->setPort($base->getPort());
                            $url->setPath(clone $base->getPath());

                            if (!$url->getPath()->isEmpty()) {
                                $url->getPath()->pop();
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
                        $url->setUsername($base->getUsername());
                        $url->setPassword($base->getPassword());
                        $url->setHost($base->getHost());
                        $url->setPort($base->getPort());
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

                        if ($url->isFlagSet(URLInternal::FLAG_AT)) {
                            $buffer .= '%40';
                        }

                        $url->setFlag(URLInternal::FLAG_AT);

                        for ($i = 0; $i < mb_strlen($buffer, $encoding); $i++) {
                            $codePoint = mb_substr($buffer, $i, 1, $encoding);

                            if (preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $codePoint)) {
                                continue;
                            }

                            if ($codePoint == ':' && $url->getPassword() === null) {
                                $url->setPassword('');
                                continue;
                            }

                            $encodedCodePoints = URLUtils::utf8PercentEncode($codePoint, URLUtils::ENCODE_SET_USERINFO);

                            if ($url->getPassword() !== null) {
                                $url->setPassword($url->getPassword() . $encodedCodePoints);
                            } else {
                                $url->setUsername($url->getUsername() . $encodedCodePoints);
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
                    if ($c == ':' && !$url->isFlagSet(URLInternal::FLAG_ARRAY)) {
                        if ($url->isSpecial() && !$buffer) {
                            // Return failure
                            return false;
                        }

                        $host = URLUtils::parseHost($buffer);

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->setHost($host);
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

                        $host = URLUtils::parseHost($buffer);

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->setHost($host);
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
                            $url->setFlag(URLInternal::FLAG_ARRAY);
                        } else if ($c == ']') {
                            $url->unsetFlag(URLInternal::FLAG_ARRAY);
                        } else {
                            $buffer .= $c;
                        }
                    }

                    break;

                case self::PORT_STATE:
                    if (preg_match(URLUtils::REGEX_ASCII_DIGITS, $c)) {
                        $buffer .= $c;
                    } else if (($c === ''/* EOF */ || $c == '/' || $c == '?' || $c == '#') || ($url->isSpecial() && $c == '\\') || $aState) {
                        if ($buffer) {
                            $port = intval($buffer, 10);

                            if ($port > pow(2, 16) - 1) {
                                // Syntax violation. Return failure.
                                return false;
                            }

                            if (array_key_exists($url->getScheme(), URLUtils::$specialSchemes) && URLUtils::$specialSchemes[$url->getScheme()] == $port) {
                                $url->setPort(null);
                            } else {
                                $url->setPort($port);
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
                    if ($url->getScheme() == 'file') {
                        if ($c === ''/* EOF */) {
                            if ($base && $base->getScheme() == 'file') {
                                $url->setHost($base->getHost());
                                $url->setPath(clone $base->getPath());
                                $url->setQuery($base->getQuery());
                            }
                        } else if ($c == '/' || $c == '\\') {
                            if ($c == '\\') {
                                // Syntax violation
                            }

                            $state = self::FILE_SLASH_STATE;
                        } else if ($c == '?') {
                            if ($base && $base->getScheme() == 'file') {
                                $url->setHost($base->getHost());
                                $url->setPath(clone $base->getPath());
                                $url->setQuery('');
                                $state = self::QUERY_STATE;
                            }
                        } else if ($c == '#') {
                            if ($base && $base->getScheme() == 'file') {
                                $url->setHost($base->getHost());
                                $url->setPath(clone $base->getPath());
                                $url->setQuery($base->getQuery());
                                $url->setFragment($base->getFragment());
                                $state = self::FRAGMENT_STATE;
                            }
                        } else {
                            // Platform-independent Windows drive letter quirk
                            if ($base && $base->getScheme() == 'file' && (
                                !preg_match(URLUtils::REGEX_WINDOWS_DRIVE_LETTER, mb_substr($input, $pointer, 2, $encoding)) ||
                                mb_strlen(mb_substr($input, $pointer, mb_strlen($input, $encoding), $encoding), $encoding) == 1 ||
                                !preg_match('/[/\\?#]/', mb_substr($input, $pointer + 2, 1, $encoding)))) {
                                $url->setHost($base->getHost());
                                $url->setPath(clone $base->getPath());
                                self::popURLPath($url);
                            } else if ($base && $base->getScheme() == 'file') {
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
                        if ($base && $base->getScheme() == 'file' && preg_match(URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER, $base->getPath()[0])) {
                            // This is a (platform-independent) Windows drive letter quirk. Both url’s and base’s
                            // host are null under these conditions and therefore not copied.
                            $url->getPath()->push($base->getPath()[0]);
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
                            $host = URLUtils::parseHost($buffer);

                            if ($host === false) {
                                // Return failure
                                return false;
                            }

                            if ($host != 'localhost') {
                                $url->setHost($host);
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
                    if ($url->isSpecial() && $c == '\\') {
                        // Syntax violation
                    }

                    $state = self::PATH_STATE;

                    if ($c != '/' && !($url->isSpecial() && $c == '\\')) {
                        $pointer--;
                    }

                    break;

                case self::PATH_STATE:
                    if ($c === ''/* EOF */ || $c == '/' || ($url->isSpecial() && $c == '\\') || (!$aState && ($c == '?' || $c == '#'))) {
                        if ($url->isSpecial() && $c == '\\') {
                            // Syntax violation
                        }

                        if (in_array($buffer, self::$doubleDotPathSegment)) {
                            self::popURLPath($url);

                            if ($c != '/' && !($url->isSpecial() && $c == '\\')) {
                                $url->getPath()->push('');
                            }
                        } else if (in_array($buffer, self::$singleDotPathSegment) && $c != '/' && !($url->isSpecial() && $c == '\\')) {
                            $url->getPath()->push('');
                        } else if (!in_array($buffer, self::$singleDotPathSegment)) {
                            if ($url->getScheme() == 'file' && $url->getPath()->isEmpty() && preg_match(URLUtils::REGEX_WINDOWS_DRIVE_LETTER, $buffer)) {
                                if ($url->getHost() !== null) {
                                    // Syntax violation
                                }

                                $url->setHost(null);
                                // This is a (platform-independent) Windows drive letter quirk.
                                $buffer = mb_substr($buffer, 0, 1, $encoding) . ':';
                            }

                            $url->getPath()->push($buffer);
                        }

                        $buffer = '';

                        if ($c == '?') {
                            $url->setQuery('');
                            $state = self::QUERY_STATE;
                        } else if ($c == '#') {
                            $url->setFragment('');
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
                        $url->setQuery('');
                        $state = self::QUERY_STATE;
                    } else if ($c == '#') {
                        $url->setFragment('');
                        $state = self::FRAGMENT_STATE;
                    } else {
                        if ($c !== ''/* EOF */ && !preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !ctype_xdigit(mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        if ($c !== ''/* EOF */ && !preg_match(URLUtils::REGEX_ASCII_WHITESPACE, $c)) {
                            if (!$url->getPath()->isEmpty()) {
                                $url->getPath()[0] .= URLUtils::utf8PercentEncode($c);
                            }
                        }
                    }

                    break;

                case self::QUERY_STATE:
                    if ($c === ''/* EOF */ || (!$aState && $c == '#')) {
                        if (!$url->isSpecial() || $url->getScheme() == 'ws' || $url->getScheme() == 'wss') {
                            $encoding = 'utf-8';
                        }

                        $buffer = mb_convert_encoding($buffer, $encoding);

                        for ($i = 0; $i < strlen($buffer); $i++) {
                            $byteOrd = ord($buffer[$i]);

                            if ($byteOrd < 0x21 || $byteOrd > 0x7E || $byteOrd == 0x22 || $byteOrd == 0x23 || $byteOrd == 0x3C || $byteOrd == 0x3E) {
                                $url->setQuery($url->getQuery() . URLUtils::percentEncode($buffer[$i]));
                            } else {
                                $url->setQuery($url->getQuery() . $buffer[$i]);
                            }
                        }

                        $buffer = '';

                        if ($c == '#') {
                            $url->setFragment('');
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

                        $url->setFragment($url->getFragment() . $c);
                    }

                    break;
            }
        }

        return $url;
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
        return array_key_exists($this->mScheme, URLUtils::$specialSchemes);
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

            $output .= URLUtils::serializeHost($this->mHost);

            if ($this->mPort !== null) {
                $output .= ':' . $this->mPort;
            }
        } else if ($this->mHost === null && $this->mScheme == 'file') {
            $output .= '//';
        }

        if ($this->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
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

            for ($i = 0; $i < mb_strlen($aPassword); $i++) {
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

        for ($i = 0; $i < mb_strlen($aUsername); $i++) {
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

        if ($url->getScheme() != 'blob') {
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
        if (!$aUrl->getPath()->isEmpty()) {
            $containsDriveLetter = false;

            foreach ($aUrl->getPath() as $path) {
                if (preg_match(URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER, $path)) {
                    $containsDriveLetter = true;
                    break;
                }
            }

            if ($aUrl->getScheme() != 'file' || !$containsDriveLetter) {
                $aUrl->getPath()->pop();
            }
        }
    }
}
