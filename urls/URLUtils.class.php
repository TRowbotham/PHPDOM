<?php
namespace phpjs\urls;

abstract class URLUtils
{
    const REGEX_C0_CONTROLS = '/[\x{0000}-\x{001F}]/';
    const REGEX_ASCII_ALPHA = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}]/';
    const REGEX_ASCII_ALPHANUMERIC = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}
        \x{0061}-\x{007A}]/';
    const REGEX_URL_CODE_POINTS = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}
        \x{0061}-\x{007A}
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
    const REGEX_ASCII_DOMAIN = '/[\x{0000}\x{0009}\x{000A}\x{000D}\x{0020}#%\/:
        ?@[\\\\\]]/';
    const REGEX_WINDOWS_DRIVE_LETTER = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}][:
        |]/';
    const REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER = '/[\x{0041}-\x{005A}
        \x{0061}-\x{007A}]:/';

    const ENCODE_SET_SIMPLE = 1;
    const ENCODE_SET_DEFAULT = 2;
    const ENCODE_SET_USERINFO = 3;

    public static $specialSchemes = [
        'ftp'    => 21,
        'file'   => '',
        'gopher' => 70,
        'http'   => 80,
        'https'  => 443,
        'ws'     => 80,
        'wss'    => 443
    ];

    /**
     * Converts a domain name to ASCII.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param string $aDomain The domain name to be converted.
     *
     * @return string|bool Returns the domain name upon success or false on
     *     failure.
     */
    public static function domainToASCII($aDomain)
    {
        // Let result be the result of running Unicode ToASCII with domain_name
        // set to domain, UseSTD3ASCIIRules set to false, processing_option set
        // to Transitional_Processing, and VerifyDnsLength set to false.
        $result = idn_to_ascii(
            $aDomain,
            IDNA_USE_STD3_RULES,
            INTL_IDNA_VARIANT_UTS46
        );

        if (!$result) {
            // Syntax violation
            return false;
        }

        return $result;
    }

    /**
     * Converts a domain name to Unicode.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param string $aDomain The domain name to be converted.
     *
     * @return string|bool Returns the domain name upon success or false on
     *     failure.
     */
    public static function domainToUnicode($aDomain)
    {
        // Let result be the result of running Unicode ToUnicode with
        // domain_name set to domain, UseSTD3ASCIIRules set to false.
        $result = idn_to_utf8(
            $aDomain,
            IDNA_USE_STD3_RULES,
            INTL_IDNA_VARIANT_UTS46
        );

        if (!$result) {
            // Syntax violation
            return false;
        }

        return $result;
    }

    public static function encode($aStream, $aEncoding = 'UTF-8')
    {
        $inputEncoding = mb_detect_encoding($aStream);

        return mb_convert_encoding($aStream, $aEncoding, $inputEncoding);
    }

    /**
     * Decodes a percent encoded byte into a code point.
     *
     * @see https://url.spec.whatwg.org/#percent-decode
     *
     * @param  string $aByteSequence A byte sequence to be decoded.
     *
     * @return string
     */
    public static function percentDecode($aByteSequence)
    {
        $output = '';

        for ($i = 0, $len = strlen($aByteSequence); $i < $len; $i++) {
            if ($aByteSequence[$i] != '%') {
                $output .= $aByteSequence[$i];
            } elseif (
                $aByteSequence[$i] == '%' &&
                !ctype_xdigit(substr($aByteSequence, $i + 1, 2))
            ) {
                $output .= $aByteSequence[$i];
            } else {
                // TODO: utf-8 decode without BOM
                $bytePoint = bin2hex(substr($aByteSequence, $i + 1, 2));
                $output .= $bytePoint;
                $i += 2;
            }
        }

        return $output;
    }

    /**
     * Encodes a byte into a uppercase hexadecimal number prefixed by a %
     * character.
     *
     * @see https://url.spec.whatwg.org/#percent-encode
     *
     * @param  string $aByte A byte to be percent encoded.
     *
     * @return string
     */
    public static function percentEncode($aByte)
    {
        return '%' . strtoupper(bin2hex($aByte));
    }

    /**
     * Serializes the individual bytes of the given byte sequence to be
     * compatible with application/x-www-form-encoded URLs.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-byte-serializer
     *
     * @param  string $aInput A byte sequence to be serialized.
     *
     * @return string
     */
    public static function urlencodedByteSerializer($aInput)
    {
        $output = '';

        for ($i = 0, $len = strlen($aInput); $i < $len; $i++) {
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
     * Encodes a byte sequence to be compatible with the
     * application/x-www-form-urlencoded encoding.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-parser
     *
     * @param string $aInput A byte sequence to be encoded.
     *
     * @param string $aEncoding Optional argument used to set the character
     *     encoding. Default is utf-8.
     *
     * @param bool $aUseCharset Optional argument that, if set to true,
     *     indicates if the charset specfied in the byte sequence should be used
     *     in place of the specified encoding argument. Default is null.
     *
     * @param bool $aIsIndex Optional argument that, if set to true, prepends
     *     an = character to the first byte sequence if one does not exist.
     *     Default is null.
     *
     * @return string[]
     */
    public static function urlencodedParser(
        $aInput,
        $aEncoding = 'utf-8',
        $aUseCharset = null,
        $aIsIndex = null
    ) {
        $input = $aInput;

        if ($aEncoding != 'utf-8') {
            for ($i = 0, $len = strlen($input); $i < $len; $i++) {
                if ($aInput[$i] > 0x7F) {
                    return false;
                }
            }
        }

        $sequences = explode('&', $input);

        if (
            $aIsIndex &&
            !empty($squences) &&
            strpos($squences[0], '=') === false
        ) {
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
                $str = substr($bytes, $pos + 1);
                $value = $str !== false ? $str : '';
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
            // TODO: Run encoding override’s decoder on the percent decoding of
            // the name and value from pairs
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
     * @see https://url.spec.whatwg.org/#concept-urlencoded-serializer
     *
     * @param string[] $aPairs A list of name-value pairs to be serialized.
     *
     * @param string $aEncoding Optionally allows you to set a different
     *     encoding to be used. Default value is UTF-8.
     *
     * @return string
     */
    public static function urlencodedSerializer(
        array $aPairs,
        $aEncoding = 'UTF-8'
    ) {
        $output = '';

        foreach ($aPairs as $key => $pair) {
            if ($key > 0) {
                $output .= '&';
            }

            $output .= self::urlencodedByteSerializer(
                mb_convert_encoding($pair['name'], $aEncoding)
            ) . '=';
            $output .= self::urlencodedByteSerializer(
                mb_convert_encoding($pair['value'], $aEncoding)
            );
        }

        return $output;
    }

    public static function urlencodedStringParser($aInput)
    {
        return self::urlencodedParser(self::encode($aInput));
    }

    /**
     * Encodes a code point stream if the code point is not part of the
     * specified encode set.
     *
     * @see https://url.spec.whatwg.org/#utf-8-percent-encode
     *
     * @param string $aCodePoint A code point stream to be encoded.
     *
     * @param int $aEncodeSet The encode set used to decide whether or not the
     *     code point should be encoded.
     *
     * @return string
     */
    public static function utf8PercentEncode(
        $aCodePoint,
        $aEncodeSet = self::ENCODE_SET_SIMPLE
    ) {
        // The Simple Encode Set
        $inCodeSet = preg_match(self::REGEX_C0_CONTROLS, $aCodePoint) ||
            ord($aCodePoint) > 0x7E;

        if (!$inCodeSet && $aEncodeSet >= self::ENCODE_SET_DEFAULT) {
            $inCodeSet = $inCodeSet || preg_match(
                '/[\x{0020}"#<>?`,{}]/',
                $aCodePoint
            );
        }

        if (!$inCodeSet && $aEncodeSet == self::ENCODE_SET_USERINFO) {
            $inCodeSet = $inCodeSet || preg_match(
                '/[\/:;=@[\\\\\]^|]/',
                $aCodePoint
            );
        }

        if (!$inCodeSet) {
            return $aCodePoint;
        }

        $bytes = self::encode($aCodePoint);
        $result = '';

        for ($i = 0, $len = strlen($bytes); $i < $len; $i++) {
            $result .= self::percentEncode($bytes[$i]);
        }

        return $result;
    }
}
