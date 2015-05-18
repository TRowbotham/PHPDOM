<?php
class URLParser {
    const STATE_SCHEME_START = 1;
    const STATE_SCHEME = 2;
    const STATE_SCHEME_DATA = 3;
    const STATE_NO_SCHEME = 4;
    const STATE_RELATIVE = 5;
    const STATE_RELATIVE_OR_AUTHORITY = 6;
    const STATE_AUTHORITY_FIRST_SLASH = 7;
    const STATE_QUERY = 8;
    const STATE_FRAGMENT = 9;
    const STATE_AUTHORITY_IGNORE_SLASHES = 10;
    const STATE_RELATIVE_SLASH = 11;
    const STATE_RELATIVE_PATH = 12;
    const STATE_FILE_HOST = 13;
    const STATE_AUTHORITY_SECOND_SLASH = 14;
    const STATE_AUTHORITY = 15;
    const STATE_HOST = 16;
    const STATE_RELATIVE_PATH_START = 17;
    const STATE_HOSTNAME = 18;
    const STATE_PORT = 19;

    const REGEX_ASCII_DIGIT = '/[\x{0030}-\x{0039}]/';
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

    const ENCODE_SET_SIMPLE = 1;
    const ENCODE_SET_DEFAULT = 2;
    const ENCODE_SET_PASSWORD = 3;
    const ENCODE_SET_USERNAME = 4;

    public static $relativeSchemes = array('ftp' => 21,
                                                'file' => '',
                                                'gopher' => 70,
                                                'http' => 80,
                                                'https' => 443,
                                                'ws' => 80,
                                                'wss' => 443);
    public static $dots = array('%2e' => '.',
                                '.%2e' => '..',
                                '%2e.' => '..',
                                '%2e%2e' => '..');

    public function __construct() {
    }

    public static function URLParser($aInput, URL $aBase = null, $aEncoding = null) {
        $url = self::basicURLParser($aInput, $aBase, $aEncoding);

        if ($url === false) {
            return false;
        }

        if ($url->mScheme != 'blob') {
            return $url;
        }

        /*if ($url->mSchemeData != blob store) {
            return $url;
        }

        $url->object = structured clone;*/

        return $url;
    }

    public static function basicURLParser($aInput, URL $aBaseUrl = null, $aEncoding = null, URL $aUrl = null, $aState = null) {
        if ($aUrl) {
            $url = $aUrl;
            $input = $aInput;
        } else {
            $url = new URL();
            $input = trim($aInput);
        }

        $state = $aState ? $aState : self::STATE_SCHEME_START;
        $base = $aBaseUrl;
        $encoding = $aEncoding ? $aEncoding : 'utf-8';
        $buffer = '';

        for ($pointer = 0; $pointer <= strlen($input); $pointer++) {
            $c = substr($input, $pointer, 1);

            switch ($state) {
                case self::STATE_SCHEME_START:
                    if (preg_match(self::REGEX_ASCII_ALPHA, $c)) {
                        $buffer .= strtolower($c);
                        $state = self::STATE_SCHEME;
                    } elseif (!$aState) {
                        $state = self::STATE_NO_SCHEME;
                        $pointer--;
                    } else {
                        // parse error
                        break;
                    }

                    break;

                case self::STATE_SCHEME:
                    if (preg_match(self::REGEX_ASCII_ALPHANUMERIC, $c) || preg_match('/[+\-.]/', $c)) {
                        $buffer .= strtolower($c);
                    } elseif ($c == ':') {
                        $url->mScheme = $buffer;
                        $buffer = '';

                        if ($aState) {
                            break;
                        }

                        if (array_key_exists($url->mScheme, self::$relativeSchemes)) {
                            $url->mFlags |= URL::FLAG_RELATIVE;
                        }

                        if ($url->mScheme == 'file') {
                            $state = self::STATE_RELATIVE;
                        } elseif ($url->mFlags & URL::FLAG_RELATIVE && $base && $base->mScheme == $url->mScheme) {
                            $state = self::STATE_RELATIVE_OR_AUTHORITY;
                        } elseif ($url->mFlags & URL::FLAG_RELATIVE) {
                            $state = self::STATE_AUTHORITY_FIRST_SLASH;
                        } else {
                            $state = self::STATE_SCHEME_DATA;
                        }
                    } elseif (!$aState) {
                        $buffer = '';
                        $state = self::STATE_NO_SCHEME;
                        $pointer = 0;
                    } elseif ($c === false) {
                        break;
                    } else {
                        // parse error
                        break;
                    }

                    break;

                case self::STATE_SCHEME_DATA:
                    if ($c == '?') {
                        $url->mQuery = '';
                        $state = self::STATE_QUERY;
                    } elseif ($c == '#') {
                        $this->mFragment = '';
                        $state = self::STATE_FRAGMENT;
                    } else {
                        if ($c !== false && !preg_match(self::REGEX_URL_CODE_POINTS, $c) &&
                            $c != '%') {
                            // parse error
                        } elseif ($c == '%' && preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($input, $pointer + 1))) {
                            // parse error
                        } elseif ($c !== false && !preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                            $url->mSchemeData .= self::utf8PercentEncode($c);
                        }
                    }

                    break;

                case self::STATE_NO_SCHEME:
                    if (!$base || !($base->mFlags & URL::FLAG_RELATIVE)) {
                        // parse error
                        return false;
                    } else {
                        $state = self::STATE_RELATIVE;
                        $pointer--;
                    }

                    break;

                case self::STATE_RELATIVE_OR_AUTHORITY:
                    if ($c == '/' && preg_match('/^\//', substr($input, $pointer + 1))) {
                        $state = self::STATE_AUTHORITY_IGNORE_SLASHES;
                        $pointer++;
                    } else {
                        // parse error
                        $state = self::STATE_RELATIVE;
                        $pointer--;
                    }

                    break;

                case self::STATE_RELATIVE:
                    $url->mFlags |= URL::FLAG_RELATIVE;

                    if ($url->mScheme != 'file') {
                        $url->mScheme = $base->mScheme;
                    }

                    if ($c === false) {
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $url->mPath = $base->mPath;
                        $url->mQuery = $base->mScheme;
                    }

                    switch ($c) {
                        case '/':
                        case '\\':
                            if ($c == '\\') {
                                // parse error
                            }

                            $state = self::STATE_RELATIVE_SLASH;

                            break;

                        case '?':
                            $url->mHost = $base->mHost;
                            $url->mPort = $base->mPort;
                            $url->mPath = $base->mPath;
                            $url->mQuery = '';
                            $state = self::STATE_QUERY;

                            break;

                        case '#':
                            $url->mHost = $base->mHost;
                            $url->mPort = $base->mPort;
                            $url->mPath = $base->mPath;
                            $url->mQuery = $base->mQuery;
                            $url->mFragment = '';
                            $state = self::STATE_FRAGMENT;

                            break;

                        default:
                            $remaining = substr($input, $pointer + 1);

                            if ($url->mScheme != 'file' || !preg_match(self::REGEX_ASCII_ALPHA, $c) ||
                                !preg_match('/^[:|]/', $remaining) || preg_match('/^[\/\\?#]/', substr($remaining, $pointer + 1))) {
                                $url->mHost = $base->mHost;
                                $url->mPort = $base->mPort;
                                $url->mPath = $base->mPath;

                                if (!$url->mPath->isEmpty()) {
                                    $url->mPath->pop();
                                }
                            }

                            $state = self::STATE_RELATIVE_PATH;
                            $pointer--;
                    }

                    break;

                case self::STATE_RELATIVE_SLASH:
                    if ($c == '/' || $c = '\\') {
                        if ($c == '\\') {
                            // parse error
                        }

                        if ($url->mScheme == 'file') {
                            $state = self::STATE_FILE_HOST;
                        } else {
                            $state = self::STATE_AUTHORITY_IGNORE_SLASHES;
                        }
                    } else {
                        if ($url->mScheme != 'file') {
                            $url->mHost = $base->mHost;
                            $url->mPort = $base->mPort;
                        }

                        $state = self::STATE_RELATIVE_PATH;
                        $pointer--;
                    }

                    break;

                case self::STATE_AUTHORITY_FIRST_SLASH:
                    if ($c == '/') {
                        $state = self::STATE_AUTHORITY_SECOND_SLASH;
                    } else {
                        // parse error
                        $state = self::STATE_AUTHORITY_IGNORE_SLASHES;
                        $pointer--;
                    }

                    break;

                case self::STATE_AUTHORITY_SECOND_SLASH:
                    if ($c == '/') {
                        $state = self::STATE_AUTHORITY_IGNORE_SLASHES;
                    } else {
                        // parse error
                        $state = self::STATE_AUTHORITY_IGNORE_SLASHES;
                        $pointer--;
                    }

                    break;

                case self::STATE_AUTHORITY_IGNORE_SLASHES:
                    if ($c != '/' && $c != '\\') {
                        $state = self::STATE_AUTHORITY;
                        $pointer--;
                    } else {
                        // parse error
                    }

                    break;

                case self::STATE_AUTHORITY:
                    if ($c == '@') {
                        if ($url->mFlags & URL::FLAG_AT) {
                            // parse error
                            $buffer = '%40' . $buffer;
                        }

                        $url->mFlags |= URL::FLAG_AT;

                        for ($i = 0; $i < strlen($buffer); $i++) {
                            if (preg_match(self::REGEX_ASCII_WHITESPACE, $buffer[$i])) {
                                // parse error
                                continue;
                            }

                            if (!preg_match(self::REGEX_URL_CODE_POINTS, $buffer[$i]) && $buffer[$i] != '%') {
                                // parse error
                            }

                            if ($buffer[$i] == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($buffer, $i + 1))) {
                                // parse error
                            }

                            if ($buffer[$i] == ':' && $url->mPassword === null) {
                                $url->mPassword = '';
                                continue;
                            }

                            $cp = self::utf8PercentEncode($buffer[$i], self::ENCODE_SET_DEFAULT);

                            if ($url->mPassword !== null) {
                                $url->mPassword .= $cp;
                            } else {
                                $url->mUsername .= $cp;
                            }
                        }

                        $buffer = '';
                    } elseif ($c === false || preg_match('/[\/\\?#]/', $c)) {
                        $pointer -= strlen($buffer) + 1;
                        $buffer = '';
                        $state = self::STATE_HOST;
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::STATE_FILE_HOST:
                    if ($c === false || preg_match('/[\/\\?#]/', $c)) {
                        $pointer--;

                        if (preg_match(self::REGEX_ASCII_ALPHA, $buffer[0]) && preg_match('/[:|]/', $buffer[1])) {
                            $state = self::STATE_RELATIVE_PATH;
                        } elseif (!$buffer) {
                            $state = self::STATE_RELATIVE_PATH_START;
                        } else {
                            $host = self::parseHost($buffer);

                            if ($host === false) {
                                return false;
                            }

                            $url->mHost = $host;
                            $buffer = '';
                            $state = self::STATE_RELATIVE_PATH_START;
                        }
                    } elseif (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                        // parse error
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::STATE_HOST:
                case self::STATE_HOSTNAME:
                    if ($c == ':' && !($url->mFlags & URL::FLAG_ARRAY)) {
                        $host = self::parseHost($buffer);

                        if ($host === false) {
                            return false;
                        }

                        $url->mHost = $host;
                        $buffer = '';
                        $state = self::STATE_PORT;

                        if ($aState == self::STATE_HOSTNAME) {
                            break;
                        }
                    } elseif ($c === false || preg_match('/[\/\\?#]/', $c)) {
                        $pointer--;
                        $host = self::parseHost($buffer);

                        if ($host === false) {
                            return false;
                        }

                        $url->mHost = $host;
                        $buffer = '';
                        $state = self::STATE_RELATIVE_PATH_START;

                        if ($aState) {
                            break;
                        }
                    } elseif (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                        // parse error
                    } else {
                        if ($c == '[') {
                            $url->mFlags |= URL::FLAG_ARRAY;
                        } elseif ($c == ']') {
                            $url->mFlags &= ~URL::FLAG_ARRAY;
                        }

                        $buffer .= $c;
                    }

                    break;

                case self::STATE_PORT:
                    if (preg_match(self::REGEX_ASCII_DIGIT, $c)) {
                        $buffer .= $c;
                    } elseif ($c === false || preg_match('/[\/\\?#]/', $c) || $aState) {
                        while (strlen($buffer) > 1) {
                            if (!preg_match('/^\x{0030}/', $buffer)) {
                                break;
                            }

                            $buffer = substr($buffer, 1);
                        }

                        if ($buffer == self::$relativeSchemes[$url->mScheme]) {
                            $buffer = '';
                        }

                        $url->mPort = $buffer;

                        if ($aState) {
                            break;
                        }

                        $buffer = '';
                        $state = self::STATE_RELATIVE_PATH_START;
                        $pointer--;
                    } elseif (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                        // parse error
                    } else {
                        // parse error
                        return false;
                    }

                    break;

                case self::STATE_RELATIVE_PATH_START:
                    if ($c == '\\') {
                        // parse error
                    }

                    $state = self::STATE_RELATIVE_PATH;

                    if ($c != '/' && $c != '\\') {
                        $pointer--;
                    }

                    break;

                case self::STATE_RELATIVE_PATH:
                    if (($c === false || $c == '/' || $c == '\\') || (!$aState &&
                        ($c == '?' || $c == '#'))) {
                        if ($c == '\\') {
                            // parse error
                        }

                        if (isset(self::$dots[strtolower($buffer)])) {
                            $buffer = self::$dots[strtolower($buffer)];
                        }

                        if ($buffer == '..') {
                            if (!$url->mPath->isEmpty()) {
                                $url->mPath->pop();
                            }

                            if ($c != '/' && $c != '\\') {
                                $url->mPath->push('');
                            }
                        } elseif ($buffer == '.' && $c != '/' && $c != '\\') {
                            $url->mPath->push('');
                        } elseif ($buffer != '.') {
                            if ($url->mScheme == 'file' && $url->mPath->isEmpty() && preg_match(self::REGEX_ASCII_ALPHA, $buffer[0]) &&
                                $buffer[1] == '|') {
                                $buffer[1] = ':';
                            }

                            $url->mPath->push($buffer);
                        }

                        $buffer = '';

                        if ($c == '?') {
                            $url->mQuery = '';
                            $state = self::STATE_QUERY;
                        } elseif ($c == '#') {
                            $url->mFragment = '';
                            $state = self::STATE_FRAGMENT;
                        }
                    } elseif (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                        // parse error
                    } else {
                        if (!preg_match(self::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // parse error
                        }

                        if ($c == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, $c)) {
                            // parse error
                        }

                        $buffer .= self::utf8PercentEncode($c, self::ENCODE_SET_DEFAULT);
                    }

                    break;

                case self::STATE_QUERY:
                    if ($c === false || (!$aState && $c == '#')) {
                        if (!($url->mFlags & URL::FLAG_RELATIVE) || $url->mScheme == 'ws' || $url->mScheme == 'wss') {
                            $aEncoding = 'utf-8';
                        }

                        for ($i = 0; $i < strlen($buffer); $i++) {
                            $byte = $buffer[$i];

                            if ($byte < 0x21 || $byte > 0x7E || $byte == 0x22 || $byte == 0x23 || $byte == 0x3C || $byte == 0x60) {
                                $url->mQuery .= self::utf8PercentEncode($byte);
                            } else {
                                $url->mQuery .= $byte;
                            }
                        }

                        $buffer = '';

                        if ($c == '#') {
                            $url->mFragment = '';
                            $state = self::STATE_FRAGMENT;
                        }
                    } elseif (preg_match(self::REGEX_ASCII_WHITESPACE, $c)) {
                        // parse error
                    } else {
                        if (!preg_match(self::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // parse error
                        } elseif ($c == '%' && !preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($input, $pointer + 1))) {
                            // parse error
                        }

                        $buffer .= $c;
                    }

                    break;

                case self::STATE_FRAGMENT:
                    if ($c === false) {
                        // Do Nothing
                    } elseif (preg_match('/[\x{0000}\x{0009}\x{000A}\x{000D}]/', $c)) {
                        // parse error
                    } else {
                        if (!preg_match(self::REGEX_URL_CODE_POINTS, $c) && $c != '%') {
                            // parse error
                        }

                        if ($c == '%' && preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($input, $pointer + 1))) {
                            // parse error
                        }

                        $url->mFragment .= $c;
                    }

                    break;
            }
        }

        return $url;
    }

    public static function parseHost($aInput, $aUnicodeFlag = null) {
        if ($aInput === '') {
            return false;
        }

        if ($aInput[0] == '[') {
            if ($aInput[strlen($aInput) - 1] != ']') {
                // parse error
                return false;
            }

            return self::IPv6Parser(substr($aInput, 1, strlen($aInput) - 2));
        }

        $domain = utf8_decode(self::percentDecode(utf8_encode($aInput)));
        $asciiDomain = URL::domainToASCII($domain);

        if ($asciiDomain === false) {
            return false;
        }

        if (preg_match(self::REGEX_ASCII_DOMAIN, $asciiDomain)) {
            return false;
        }

        return $aUnicodeFlag ? self::domainToUnicode($domain) : $asciiDomain;
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

    // https://url.spec.whatwg.org/#concept-urlencoded-parser
    public static function urlencodedParser($aInput, $aEncoding = 'utf-8', $aUseCharset = null, $aIsIndex = null) {
        $input = $aInput;

        if ($aEncoding != 'utf-8') {
            for ($i = 0; $i < strlen($input); $i++) {
                if ($aInput[$i] > 0x7F) {
                    return false;
                }
            }
        } else {
            $input = utf8_encode($aInput);
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

            $pairs[] = array('name' => $name, 'value' => $value);
        }

        return $pairs;
    }

    public static function urlencodedSerializer(array $aList, $aEncoding = 'utf-8') {
        $output = '';

        foreach ($aList as $key => $pair) {
            if ($key > 0) {
                $output .= '&';
            }

            $output .= self::urlencodedByteSerializer($pair['name']) . '=' . self::urlencodedByteSerializer($pair['value']);
        }

        return $output;
    }

    public static function urlencodedByteSerializer($aInput) {
        $output = '';

        for ($i = 0; $i < strlen($aInput); $i++) {
            $byte = hexdec(bin2hex(mb_substr($aInput[$i], 0, 1, 'utf-8')));

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
        return self::urlencodedParser(utf8_encode($aInput));
    }

    public static function utf8PercentEncode($aCodePoint, $aEncodeSet = self::ENCODE_SET_SIMPLE) {
        switch ($aEncodeSet) {
            case self::ENCODE_SET_SIMPLE:
                $notInCodeSet = !preg_match('/[^\x{0020}-\x{007E}]/', $aCodePoint);

                break;

            case self::ENCODE_SET_DEFAULT:
                $notInCodeSet = !preg_match('/[^\x{0020}-\x{007E}]/', $aCodePoint) && !preg_match('/[\x{0020}"#<>?`]/', $aCodePoint);

                break;

            case self::ENCODE_SET_PASSWORD:
                $notInCodeSet = !preg_match('/[^\x{0020}-\x{007E}]/', $aCodePoint) && !preg_match('/[\x{0020}"#<>?`\/@\\]/', $aCodePoint);

                break;

            case self::ENCODE_SET_USERNAME:
                $notInCodeSet = !preg_match('/[^\x{0020}-\x{007E}]/', $aCodePoint) && !preg_match('/[\x{0020}"#<>?`\/@\\:]/', $aCodePoint);

                break;
        }

        if ($notInCodeSet) {
            return $aCodePoint;
        }

        $bytes = utf8_encode($aCodePoint);
        $result = '';

        for ($i = 0; $i < strlen($bytes); $i++) {
            $result .= self::percentEncode($bytes[$i]);
        }

        return $result;
    }

    public static function percentEncode($aByte) {
        return '%' . strtoupper(bin2hex($aByte));
    }

    public static function percentDecode($aByteSequence) {
        $output = '';

        for ($i = 0; $i < strlen($aByteSequence); $i++) {
            if ($aByteSequence[$i] != '%') {
                $output .= $aByteSequence[$i];
            } elseif ($aByteSequence[$i] == '%' && preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($aByteSequence, $i + 1)) === false) {
                $output .= $aByteSequence[$i];
            } else {
                preg_match(self::REGEX_ASCII_HEX_DIGITS, substr($aByteSequence, $i + 1), $matches);

                $bytePoint = bin2hex(utf8_decode($matches[0][0]));
                $output .= $bytePoint;
                $i += 2;
            }
        }

        return $output;
    }

    public static function serializeHost($aHost = null) {
        if ($aHost === null) {
            return '';
        }

        if (self::IPv6Parser($aHost)) {
            return '[' . self::serializeIPv6($aHost) . ']';
        }

        return $aHost;
    }

    public static function serializeIPv6($aAddress) {
        $output = '';
        $compressPointer = null;

        return $output;
    }

    public static function serializeURL(URL $aUrl, $aExcludeFragment = null) {
        $output = $aUrl->mScheme . ':';

        if ($aUrl->mFlags & URL::FLAG_RELATIVE) {
            $output .= '//';

            if ($aUrl->mUsername || $aUrl->mPassword !== null) {
                $output .= $aUrl->mUsername;

                if ($aUrl->mPassword !== null) {
                    $output .= ':' . $aUrl->mPassword;
                }

                $output .= '@';
            }

            $output .= self::serializeHost($aUrl->mHost);

            if ($aUrl->mPort) {
                $output .= ':' . $aUrl->mPort;
            }

            $output .= '/';

            foreach ($aUrl->mPath as $key => $path) {
                if ($key > 0) {
                    $output .= '/';
                }

                $output .= $path;
            }
        } elseif (!($aUrl->mFlags & URL::FLAG_RELATIVE)) {
            $output .= $aUrl->mSchemeData;
        }

        if ($aUrl->mQuery !== null) {
            $output .= '?' . $aUrl->mQuery;
        }

        if (!$aExcludeFragment && $aUrl->mFragment !== null) {
            $output .= '#' . $aUrl->mFragment;
        }

        return $output;
    }
}
