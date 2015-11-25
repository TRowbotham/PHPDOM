<?php
namespace phpjs\urls;

use phpjs\urls;

require_once 'URLInternal.class.php';

class URLParser {
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

    const REGEX_C0_CONTROLS = '/[\x{0000}-\x{001F}]/';
    const REGEX_ASCII_DIGITS = '/[\x{0030}-\x{0039}]/';
    const REGEX_ASCII_HEX_DIGITS = '/^[\x{0030}-\x{0039}\x{0041}-\x{0046}\x{0061}-\x{0066}]{2}/';
    const REGEX_ASCII_HEX_DIGIT = '/[\x{0030}-\x{0039}\x{0041}-\x{0046}\x{0061}-\x{0066}]/';
    const REGEX_ASCII_ALPHA = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}]/';
    const REGEX_ASCII_ALPHANUMERIC = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}\x{0061}-\x{007A}]/';
    const REGEX_URL_CODE_POINTS = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}\x{0061}-\x{007A}
            !$&\'()*+,\-.\/:;=?@_~
            \x{00A0}-\x{D7DD}
            \x{E000}-\x{FDCF}
            \x{FDF0}-\x{FFFD}
            \x{10000}-\x{1FFFD}
            \x{20000}-\x{2FFFD}
            \x{30000}-\x{3FFFD}
            \x{40000}-\x{4FFFD}
            \x{50000}-\x{5FFFD}
            \x{60000}-\x{6FFFD}
            \x{70000}-\x{7FFFD}
            \x{80000}-\x{8FFFD}
            \x{90000}-\x{9FFFD}
            \x{A0000}-\x{AFFFD}
            \x{B0000}-\x{BFFFD}
            \x{C0000}-\x{CFFFD}
            \x{D0000}-\x{DFFFD}
            \x{E0000}-\x{EFFFD}
            \x{F0000}-\x{FFFFD}
            \x{100000}-\x{10FFFD}
             ]/u';
    const REGEX_ASCII_WHITESPACE = '/[\x{0009}\x{000A}\x{000D}]/';
    const REGEX_ASCII_DOMAIN = '/[\x{0000}\x{0009}\x{000A}\x{000D}\x{0020}#%\/:?@[\\\]]/';
    const REGEX_WINDOWS_DRIVE_LETTER = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}][:|]/';
    const REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}]:/';

    const ENCODE_SET_SIMPLE = 1;
    const ENCODE_SET_DEFAULT = 2;
    const ENCODE_SET_USERINFO = 3;

    public static $specialSchemes = array('ftp' => 21,
                                        'file' => '',
                                        'gopher' => 70,
                                        'http' => 80,
                                        'https' => 443,
                                        'ws' => 80,
                                        'wss' => 443);

    public static $singleDotPathSegment = array('.' => '.',
                                                '%2e', '.');
    public static $doubleDotPathSegment = array('..' => '..',
                                                '.%2e' => '..',
                                                '%2e.' => '..',
                                                '%2e%2e' => '..');

    public function __construct() { }

    /**
     * Parses a string as a URL.  The string can be an absolute URL or a relative URL.  If a relative URL is give,
     * a base URL must also be given so that a complete URL can be resolved.  It can also parse individual parts of a URL
     * when the state machine starts in a specific state.
     *
     * @link https://url.spec.whatwg.org/#concept-basic-url-parser
     *
     * @param  string   $aInput    The URL string that is to be parsed.
     *
     * @param  URL|null $aBaseUrl  Optional argument that is only needed if the input is a relative URL.  This represents the base URL,
     *                             which in most cases, is the document's URL, it may also be a node's base URI or whatever base URL you
     *                             wish to resolve relative URLs against. Default is null.
     *
     * @param  string   $aEncoding Optional argument that overrides the default encoding.  Default is UTF-8.
     *
     * @param  URL|null $aUrl      Optional argument.  This represents an existing URL object that should be modified based on the input
     *                             URL and optional base URL.  Default is null.
     *
     * @param  int      $aState    Optional argument. An integer that determines what state the state machine will begin parsing the
     *                             input URL from.  Suppling a value for this parameter will override the default state of SCHEME_START_STATE.
     *                             Default is null.
     *
     * @return URL|bool            Returns a URL object upon successfully parsing the input or false if parsing input failed.
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
                    if (preg_match(self::REGEX_ASCII_ALPHA, $c)) {
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
                    if (preg_match(self::REGEX_ASCII_ALPHANUMERIC, $c) || preg_match('/[+\-.]/', $c)) {
                        $buffer .= strtolower($c);
                    } elseif ($c == ':') {
                        if ($aState) {
                            $bufferIsSpecialScheme = false;

                            foreach (self::$specialSchemes as $scheme => $port) {
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

                            if (preg_match(self::REGEX_ASCII_WHITESPACE, $codePoint)) {
                                continue;
                            }

                            if ($codePoint == ':' && $url->getPassword() === null) {
                                $url->setPassword('');
                                continue;
                            }

                            $encodedCodePoints = self::utf8PercentEncode($codePoint, self::ENCODE_SET_USERINFO);

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

                        $host = self::parseHost($buffer);

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

                        $host = self::parseHost($buffer);

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
                    } else if (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
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
                    if (preg_match(self::REGEX_ASCII_DIGITS, $c)) {
                        $buffer .= $c;
                    } else if (($c === ''/* EOF */ || $c == '/' || $c == '?' || $c == '#') || ($url->isSpecial() && $c == '\\') || $aState) {
                        if ($buffer) {
                            $port = intval($buffer, 10);

                            if ($port > pow(2, 16) - 1) {
                                // Syntax violation. Return failure.
                                return false;
                            }

                            if (array_key_exists($url->getScheme(), self::$specialSchemes) && self::$specialSchemes[$url->getScheme()] == $port) {
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
                    } else if (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
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
                                !preg_match(self::REGEX_WINDOWS_DRIVE_LETTER, mb_substr($input, $pointer, 2, $encoding)) ||
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
                        if ($base && $base->getScheme() == 'file' && preg_match(self::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER, $base->getPath()[0])) {
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


                        if (preg_match(self::REGEX_WINDOWS_DRIVE_LETTER, $buffer)) {
                            // This is a (platform-independent) Windows drive letter quirk. buffer is not reset here and instead used in the path state.
                            // Syntax violation
                            $state = self::PATH_STATE;
                        } else if (!$buffer) {
                            $state = self::PATH_START_STATE;
                        } else {
                            $host = self::parseHost($buffer);

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
                    } else if (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
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

                    if ($c != '/' && !($url->isSpecial() && $c != '\\')) {
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
                            if ($url->getScheme() == 'file' && $url->getPath()->isEmpty() && preg_match(self::REGEX_WINDOWS_DRIVE_LETTER, $buffer)) {
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
                    } else if (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                        // Syntax violation
                    } else {
                        if (!preg_match(self::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        $buffer .= self::utf8PercentEncode($c, self::ENCODE_SET_DEFAULT);
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
                        if ($c !== ''/* EOF */ && !preg_match(self::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        if ($c !== ''/* EOF */ && !preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                            if (!$url->getPath()->isEmpty()) {
                                $url->getPath()[0] .= self::utf8PercentEncode($c);
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
                                $url->setQuery($url->getQuery() . self::percentEncode($buffer[$i]));
                            } else {
                                $url->setQuery($url->getQuery() . $buffer[$i]);
                            }
                        }

                        $buffer = '';

                        if ($c == '#') {
                            $url->setFragment('');
                            $state = self::FRAGMENT_STATE;
                        }
                    } else if (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                        // Syntax violation
                    } else {
                        if (!preg_match(self::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        $buffer .= $c;
                    }

                    break;

                case self::FRAGMENT_STATE:
                    if ($c === ''/* EOF */) {
                        // Do nothing
                    } else if (preg_match(self::REGEX_ASCII_WHITESPACE, $c) || preg_match('/\x{0000}/', $c)) {
                        // Syntax violation
                    } else {
                        if (!preg_match(self::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // Syntax violation
                        }

                        if ($c == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, mb_substr($input, $pointer + 1, 2, $encoding))) {
                            // Syntax violation
                        }

                        $url->setFragment($url->getFragment() . $c);
                    }

                    break;
            }
        }

        return $url;
    }

    /**
     * Converts a domain name to ASCII.
     *
     * @link https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param  string       $aDomain    The domain name to be converted.
     *
     * @return string|bool              Returns the domain name upon success or false on failure.
     */
    public static function domainToASCII($aDomain) {
        // TODO: Let result be the result of running Unicode ToASCII with domain_name set to domain, UseSTD3ASCIIRules set to false,
        // processing_option set to Transitional_Processing, and VerifyDnsLength set to false.

        // TODO: If result is a failure value, syntax violation, return failure.

        return $aDomain;
    }

    /**
     * Converts a domain name to Unicode.
     *
     * @link https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param  string       $aDomain    The domain name to be converted.
     *
     * @return string|bool              Returns the domain name upon success or false on failure.
     */
    public static function domainToUnicode($aDomain) {
        // TODO: Let result be the result of running Unicode ToUnicode with domain_name set to domain, UseSTD3ASCIIRules set to false.

        // TODO: Signify syntax violations for any returned errors, and then, return result.

        return $aDomain;
    }

    /**
     * Parses a host.
     *
     * @link https://url.spec.whatwg.org/#concept-host-parser
     *
     * @param  string                   $aInput       A IPv4, IPv6 address, or a domain.
     *
     * @param  bool|null                $aUnicodeFlag Option argument, that when set to true, causes the domain
     *                                                to be encoded using unicode instead of ASCII.  Default is null.
     *
     * @return string|GMP|string[]|bool
     */
    public static function parseHost($aInput, $aUnicodeFlag = null) {
        if ($aInput[0] == '[') {
            if ($aInput[strlen($aInput) - 1] != ']') {
                // Syntax violation
                return false;
            }

            return self::IPv6Parser(substr($aInput, 1, strlen($aInput) - 2));
        }

        // TODO: Let domain be the result of utf-8 decode without BOM on the percent decoding of utf-8 encode on input
        $domain = self::percentDecode(self::encode($aInput));
        $asciiDomain = self::domainToASCII($domain);

        if ($asciiDomain === false) {
            return false;
        }

        if (preg_match(self::REGEX_ASCII_DOMAIN, $asciiDomain)) {
            // Syntax violation
            return false;
        }

        $ipv4Host = self::parseIPv4Address($asciiDomain);

        if ($ipv4Host instanceof \GMP || $ipv4Host === false ||
            (is_resource($ipv4Host) && get_resource_type($ipv4Host) == 'gmp_resource')) {
            return $ipv4Host;
        }

        return $aUnicodeFlag ? self::domainToUnicode($domain) : $asciiDomain;
    }

    /**
     * Takes a string and parses it as an IPv4 address.
     *
     * @link https://url.spec.whatwg.org/#concept-ipv4-parser
     *
     * @param  string           $aInput  A string representing an IPv4 address.
     *
     * @return GMP|string|bool           Returns a GMP object if the input is a valid IPv4 address
     *                                   or a string if the input is determined to be a domain.  This
     *                                   will return false if the input is neither a domain or IPv4 address.
     */
    public static function parseIPv4Address($aInput) {
        $syntaxViolationFlag = null;
        $parts = explode('.', $aInput);
        $len = count($parts);
        $lastIndex = $len - 1;

        if ($parts[$lastIndex] === '') {
            $syntaxViolationFlag = true;
            array_pop($parts);
        }

        if ($len > 4) {
            return $aInput;
        }

        $numbers = array();

        foreach($parts as $part) {
            if ($part === '') {
                return $aInput;
            }

            $n = self::parseIPv4Number($part, $syntaxViolationFlag);

            if ($n === false) {
                return $aInput;
            }

            $numbers[] = $n;
        }

        if ($syntaxViolationFlag) {
            // Syntax violation
        }

        foreach ($numbers as $n) {
            if ($n > 255) {
                // Syntax violation
            }
        }

        $numCount = count($numbers);

        for ($i = 0; $i < $numCount - 1; $i++) {
            if ($numbers[$i] > 255) {
                return false;
            }
        }

        if ($numbers[$numCount - 1] >= pow(256, 5 - $numCount)) {
            // Syntax violation
            return false;
        }

        $ipv4 = gmp_init(array_pop($numbers), 10);
        $counter = 0;

        foreach ($numbers as $n) {
            $ipv4 = gmp_add($ipv4, gmp_mul(gmp_init($n, 10), gmp_init(pow(256, 3 - $counter), 10)));
            $counter++;
        }

        return $ipv4;
    }

    /**
     * Takes a string and parses it as a valid IPv4 number.
     *
     * @link https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @param  string       $aInput                 A string of numbers to be parsed.
     *
     * @param  bool|null    &$aSyntaxViolationFlag  A flag that represents if there was a syntax violation
     *                                              while parsing.
     *
     * @return int|bool                             Returns a bool on failure and an int otherwise.
     */
    public static function parseIPv4Number($aInput, &$aSyntaxViolationFlag) {
        $input = $aInput;
        $R = 10;

        if (strlen($input) > 1 && stripos($input, '0x') === 0) {
            $aSyntaxViolationFlag = true;
            $input = substr($input, 2);
            $R = 16;
        }

        if ($input === '') {
            return 0;
        } else if (strlen($input) > 1 && $input[0] === '0') {
            $syntaxViolationFlag = true;
            $input = substr($input, 1);
            $R = 8;
        }

        if (($R == 10 && preg_match('/[^0-9]/', $input)) ||
            ($R == 16 && preg_match('/[^0-9A-Fa-f]/', $input)) ||
            ($R == 8 && preg_match('/[^0-7]/', $input))) {
            return false;
        }

        // TODO: Return the mathematical integer value that is represented by input in
        // radix-R notation, using ASCII hex digits for digits with values 0 through 15.
        return intval($input, $R);
    }

    public static function IPv6Parser($aInput) {
        $address = '0:0:0:0:0:0:0:0';
        $piecePointer = 0;
        $piece = substr($address, $piecePointer, 1);
        $compressPointer = null;
        $pointer = 0;
        $c = substr($aInput, $pointer, 1);

        if ($c == ':') {
            if (substr($aInput, $pointer + 1, 1) != ':') {
                // parse error
                return false;
            }

            $pointer += 2;
            $piecePointer++;
            $compressPointer = $piecePointer;
        }

        Main:
        while ($c !== false) {
            if ($piecePointer == 8) {
                // parse error
                return false;
            }

            if ($c == ':') {
                if ($compressPointer !== null) {
                    // parse error
                    return false;
                }

                $pointer++;
                $c = substr($aInput, $pointer, 1);
                $piecePointer++;
                $compressPointer = $piecePointer;
                goto Main;
            }

            $value = 0;
            $length = 0;

            while ($length < 4 && preg_match(self::REGEX_ASCII_HEX_DIGIT, $c)) {
                $value = bin2hex($value * 0x10 + $c);
                $pointer++;
                $length++;
                $c = substr($aInput, $pointer, 1);
            }

            if ($c == '.') {
                if ($length == 0) {
                    // parse error
                    return false;
                }

                $pointer -= $length;
                $c = substr($aInput, $pointer, 1);
                goto IPv4;
            } elseif ($c == ':') {
                $pointer++;
                $c = substr($aInput, $pointer, 1);

                if ($c === false) {
                    // parse error
                    return false;
                }
            } elseif ($c !== false) {
                // parse error
                return false;
            }

            $piece = $value;
            $piecePointer++;
        }

        if ($c === false) {
            goto Finale;
        }

        IPv4:
        if ($piecePointer > 6) {
            // parse error
            return false;
        }

        $dotsSeen = 0;

        while ($c !== false) {
            $value = null;

            if (!preg_match(self::REGEX_ASCII_HEX_DIGIT, $c)) {
                // parse error
                return false;
            }

            while (preg_match(self::REGEX_ASCII_HEX_DIGIT, $c)) {
                $number = (float) $c;

                if ($value === null) {
                    $value = $number;
                } elseif ($value === 0) {
                    // parse error
                    return false;
                } else {
                    $value = $value * 10 + $number;
                }

                $pointer++;
                $c = substr($aInput, $pointer, 1);

                if ($value > 255) {
                    // parse error
                    return false;
                }
            }

            if ($dotsSeen < 3 && $c != '.') {
                // parse error
                return false;
            }

            $piece = $piece * 0x100 + $value;

            if ($dotsSeen == 1 || $dotsSeen == 3) {
                $piecePointer++;
            }

            $pointer++;
            $c = substr($aInput, $pointer, 1);

            if ($dotsSeen == 3 && $c !== false) {
                // parse error
                return false;
            }

            $dotsSeen++;
        }

        Finale:
        if ($compressPointer !== null) {
            $swaps = $piecePointer - $compressPointer;
            $piecePointer = 7;

            while ($piecePointer !== 0 && $swaps > 0) {

            }
        } elseif ($compressPointer === null && $piecePointer != 8) {
            // parse error
            return false;
        }

        return $address;
    }

    /**
     * Removes the last string from a URL's path if its scheme is not "file"
     * and the path does not contain a normalized Windows drive letter.
     *
     * @link https://url.spec.whatwg.org/#pop-a-urls-path
     *
     * @param  URLInternal $aUrl The URL of the path that is to be popped.
     */
    public static function popURLPath(URLInternal $aUrl) {
        if (!$aUrl->getPath()->isEmpty()) {
            $containsDriveLetter = false;

            foreach ($aUrl->getPath() as $path) {
                if (preg_match(self::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER, $path)) {
                    $containsDriveLetter = true;
                    break;
                }
            }

            if ($aUrl->getScheme() != 'file' || !$containsDriveLetter) {
                $aUrl->getPath()->pop();
            }
        }
    }

    /**
     * Encodes a byte sequence to be compatible with the application/x-www-form-urlencoded encoding.
     *
     * @link https://url.spec.whatwg.org/#concept-urlencoded-parser
     *
     * @param  string   $aInput      A byte sequence to be encoded.
     *
     * @param  string   $aEncoding   Optional argument used to set the character encoding.  Default is utf-8.
     *
     * @param  bool     $aUseCharset Optional argument that, if set to true, indicates if the charset specfied in the byte
     *                               sequence should be used in place of the specified encoding argument.  Default is null.
     *
     * @param  bool     $aIsIndex    Optional argument that, if set to true, prepends an = character to the first
     *                               byte sequence if one does not exist.  Default is null.
     *
     * @return string[]
     */
    public static function urlencodedParser($aInput, $aEncoding = 'utf-8', $aUseCharset = null, $aIsIndex = null) {
        $input = $aInput;

        if ($aEncoding != 'utf-8') {
            for ($i = 0; $i < strlen($input); $i++) {
                if ($aInput[$i] > 0x7F) {
                    return false;
                }
            }
        }

        $sequences = explode('&', $input);

        if ($aIsIndex && !empty($squences) && strpos($squences[0], '=') === false) {
            $sequences[0] = '=' . $sequences[0];
        }

        $pairs = array();

        foreach ($sequences as $bytes) {
            if ($bytes === '') {
                continue;
            }

            $pos = strpos($bytes, '=');

            if ($pos !== false) {
                $name = substr($bytes, 0, $pos);
                $value = substr($bytes, $pos + 1) !== false ? substr($bytes, $pos + 1) : '';
            } else {
                $name = $bytes;
                $value = '';
            }

            $name = str_replace('+', chr(0x20), $name);
            $value = str_replace('+', chr(0x20), $value);

            // TODO: If use _charset_ flag is set and name is `_charset_`

            $pairs[] = array('name' => $name, 'value' => $value);
        }

        $output = array();

        foreach ($pairs as $pair) {
            // TODO: Run encoding override’s decoder on the percent decoding of the name and value from pairs
            $output[] = array(
                'name' => self::percentDecode($pair['name']),
                'value' => self::percentDecode($pair['value'])
            );
        }

        return $output;
    }

    /**
     * Serializes a list of name-value pairs to be used in a URL.
     *
     * @link https://url.spec.whatwg.org/#concept-urlencoded-serializer
     *
     * @param  array  $aPairs    A list of name-value pairs to be serialized.
     *
     * @param  string $aEncoding Optionally allows you to set a different encoding to be used.
     *                           Default value is UTF-8.
     *
     * @return string
     */
    public static function urlencodedSerializer(array $aPairs, $aEncoding = 'UTF-8') {
        $output = '';

        foreach ($aPairs as $key => $pair) {
            if ($key > 0) {
                $output .= '&';
            }

            $output .= self::urlencodedByteSerializer(mb_convert_encoding($pair['name'], $aEncoding)) . '=';
            $output .= self::urlencodedByteSerializer(mb_convert_encoding($pair['value'], $aEncoding));
        }

        return $output;
    }

    /**
     * Serializes the individual bytes of the given byte sequence to be compatible with
     * application/x-www-form-encoded URLs.
     *
     * @link https://url.spec.whatwg.org/#concept-urlencoded-byte-serializer
     *
     * @param  string $aInput A byte sequence to be serialized.
     *
     * @return string
     */
    public static function urlencodedByteSerializer($aInput) {
        $output = '';

        for ($i = 0; $i < strlen($aInput); $i++) {
            $byte = ord($aInput[$i]);

            switch (true) {
                case ($byte == 0x20):
                    $output .= chr(0x2B);

                    break;

                case ($byte == 0x2A):
                case ($byte == 0x2D):
                case ($byte == 0x2E):
                case !($byte < 0x30 || $byte > 0x39):
                case !($byte < 0x41 || $byte > 0x5A):
                case ($byte == 0x5F):
                case !($byte < 0x61 || $byte > 0x7A):
                    $output .= $aInput[$i];

                    break;

                default:
                    $output .= self::percentEncode($aInput[$i]);
            }
        }

        return $output;
    }

    public static function urlencodedStringParser($aInput) {
        return self::urlencodedParser(self::encode($aInput));
    }

    /**
     * Encodes a code point stream if the code point is not part of the specified encode set.
     *
     * @link https://url.spec.whatwg.org/#utf-8-percent-encode
     *
     * @param  string   $aCodePoint A code point stream to be encoded.
     *
     * @param  int      $aEncodeSet The encode set used to decide whether or not the code point should
     *                              be encoded.
     * @return string
     */
    public static function utf8PercentEncode($aCodePoint, $aEncodeSet = self::ENCODE_SET_SIMPLE) {
        // The Simple Encode Set
        $inCodeSet = preg_match(self::REGEX_C0_CONTROLS, $aCodePoint) || ord($aCodePoint) > 0x7E;

        if (!$inCodeSet && $aEncodeSet <= self::ENCODE_SET_DEFAULT) {
            $inCodeSet = $inCodeSet || preg_match('/[\x{0020}"#<>?`,{}]/', $aCodePoint);
        }

        if (!$inCodeSet && $aEncodeSet <= self::ENCODE_SET_USERINFO) {
            $inCodeSet = $inCodeSet || preg_match('/[\/:;=@[\\\]^|]/', $aCodePoint);
        }

        if (!$inCodeSet) {
            return $aCodePoint;
        }

        $bytes = self::encode($aCodePoint);
        $result = '';

        for ($i = 0; $i < strlen($bytes); $i++) {
            $result .= self::percentEncode($bytes[$i]);
        }

        return $result;
    }

    /**
     * Encodes a byte into a uppercase hexadecimal number prefixed by a % character.
     *
     * @link https://url.spec.whatwg.org/#percent-encode
     *
     * @param  string $aByte A byte to be percent encoded.
     *
     * @return string
     */
    public static function percentEncode($aByte) {
        return '%' . strtoupper(bin2hex($aByte));
    }

    /**
     * Decodes a percent encoded byte into a code point.
     *
     * @link https://url.spec.whatwg.org/#percent-decode
     *
     * @param  string $aByteSequence A byte sequence to be decoded.
     *
     * @return string
     */
    public static function percentDecode($aByteSequence) {
        $output = '';

        for ($i = 0; $i < strlen($aByteSequence); $i++) {
            if ($aByteSequence[$i] != '%') {
                $output .= $aByteSequence[$i];
            } elseif ($aByteSequence[$i] == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($aByteSequence, $i + 1, 2))) {
                $output .= $aByteSequence[$i];
            } else {
                preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($aByteSequence, $i + 1, 2), $matches);

                // TODO: utf-8 decode without BOM
                $bytePoint = bin2hex($matches[0][0]);
                $output .= $bytePoint;
                $i += 2;
            }
        }

        return $output;
    }

    /**
     * Serializes a host.
     *
     * @link https://url.spec.whatwg.org/#concept-host-serializer
     *
     * @param  string|GMP|string[] $aHost A domain or an IPv4 or IPv6 address.
     *
     * @return string
     */
    public static function serializeHost($aHost) {
        if (self::IPv6Parser($aHost)) {
            return '[' . self::serializeIPv6($aHost) . ']';
        }

        return $aHost;
    }

    /**
     * Serializes an IPv4 address
     *
     * @link https://url.spec.whatwg.org/#concept-ipv4-serializer
     *
     * @param  GMP      $aAddress The IPv4 address to be serialized.
     *
     * @return string
     */
    public static function serializeIPv4Address($aAddress) {
        $output = '';
        $n = $aAddress;

        for ($i = 0; $i < 4; $i++) {
            $output = gmp_strval(gmp_mod($n, '256')) . $output;

            if ($i < 3) {
                $output = '.' . $output;
            }

            $n = gmp_div($n, '256');
        }

        return $output;
    }

    /**
     * Serializes an IPv6 address.
     *
     * @link https://url.spec.whatwg.org/#concept-ipv6-serializer
     *
     * @param  string[] $aAddress A list of 16-bit pieces representing an IPv6 address.
     *
     * @return string
     */
    public static function serializeIPv6Address($aAddress) {
        $output = '';
        $compressPointer = null;

        return $output;
    }

    /**
     * Serializes a URL object.
     *
     * @link https://url.spec.whatwg.org/#concept-url-serializer
     *
     * @param  URL          $aUrl             The URL object to serialize.
     *
     * @param  bool|null    $aExcludeFragment Optional argument, that, when specified will exclude the URL's
     *                                        fragment from being serialized.
     * @return string
     */
    public static function serializeURL(URLInternal $aUrl, $aExcludeFragment = null) {
        $output = $aUrl->getScheme() . ':';

        if ($aUrl->getHost() !== null) {
            $output .= '//';

            if ($aUrl->getUsername() !== '' || $aUrl->getPassword() !== null) {
                $output .= $aUrl->getUsername();

                if ($aUrl->getPassword() !== null) {
                    $output .= ':' . $aUrl->getPassword();
                }

                $output .= '@';
            }

            $output .= self::serializeHost($aUrl->getHost());

            if ($aUrl->getPort() !== null) {
                $output .= ':' . $aUrl->getPort();
            }
        } else if ($aUrl->getHost() === null && $aUrl->getScheme() == 'file') {
            $output .= '//';
        }

        if ($aUrl->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
            $output .= $aUrl->getPath()[0];
        } else {
            $output .= '/';

            foreach ($aUrl->getPath() as $key => $path) {
                if ($key > 0) {
                    $output .= '/';
                }

                $output .= $path;
            }
        }

        if ($aUrl->getQuery() !== null) {
            $output .= '?' . $aUrl->getQuery();
        }

        if (!$aExcludeFragment && $aUrl->getFragment() !== null) {
            $output .= '#' . $aUrl->getFragment();
        }

        return $output;
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

    public static function utf8decode($aStream, $aEncoding = 'UTF-8') {
        return mb_convert_encoding($aStream, $aEncoding, 'UTF-8');
    }

    public static function encode($aStream, $aEncoding = 'UTF-8') {
        $inputEncoding = mb_detect_encoding($aStream);

        return mb_convert_encoding($aStream, $aEncoding, $inputEncoding);
    }
}
