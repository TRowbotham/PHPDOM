<?php
namespace phpjs\urls;

abstract class URLUtils {
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
    const REGEX_ASCII_DOMAIN = '/[\x{0000}\x{0009}\x{000A}\x{000D}\x{0020}#%\/:?@[\\\\\]]/';
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

    public static function encode($aStream, $aEncoding = 'UTF-8') {
        $inputEncoding = mb_detect_encoding($aStream);

        return mb_convert_encoding($aStream, $aEncoding, $inputEncoding);
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

        $hostParts = explode('.', self::serializeHost($aOrigin['host']));
        $result .= implode('.', array_map(array('self', 'domainToUnicode'), $hostParts));

        if ($aOrigin['port'] != self::$specialSchemes[$aOrigin['scheme']]) {
            $result .= ':' . intval($aOrigin['port'], 10);
        }

        return $result;
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

        if (!$inCodeSet && $aEncodeSet >= self::ENCODE_SET_DEFAULT) {
            $inCodeSet = $inCodeSet || preg_match('/[\x{0020}"#<>?`,{}]/', $aCodePoint);
        }

        if (!$inCodeSet && $aEncodeSet == self::ENCODE_SET_USERINFO) {
            $inCodeSet = $inCodeSet || preg_match('/[\/:;=@[\\\\\]^|]/', $aCodePoint);
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
}
