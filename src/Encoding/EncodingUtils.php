<?php
namespace Rowbot\DOM\Encoding;

abstract class EncodingUtils
{
    private static $mbDetectOrder = [
        'UTF-8',
        'ISO-8859-15',
        'ISO-8859-1',
        'ASCII'
    ];
    private static $useIntlChar;

    /**
     * @see https://encoding.spec.whatwg.org/#decode
     * @param  ByteStream $stream   [description]
     * @param  [type]     $encoding [description]
     * @return [type]                [description]
     */
    public static function decode(ByteStream $stream, $encoding)
    {
        $encoding = $encoding;
        $buffer = '';
        $BOMSeen = false;
        $byteCount = 0;

        while (!$stream->isEOS()) {
            $buffer .= $stream->get();

            if (++$byteCount == 3) {
                break;
            }
        }

        if ($buffer === "\xEF\xBB\xBF") {
            $encoding = 'utf-8';
            $BOMSeen = true;
        } elseif ($buffer[0] === "\xFE" && $buffer[1] === "\xFF") {
            $encoding = 'utf-16be';
            $BOMSeen = true;
        } elseif ($buffer[0] === "\xFF" && $buffer[1] === "\xFE") {
            $encoding = 'utf-16le';
            $BOMSeen = true;
        }

        if (!$BOMSeen) {
            $stream->prependData($buffer);
        } else {
            $stream->prependData($buffer[strlen($buffer) - 1]);
        }

        $output = new CodePointStream();
        // Run encodings decoder with stream and output

        return $output;
    }
    /**
     * @see https://encoding.spec.whatwg.org/#encode
     * @param  CodePointStream $stream   [description]
     * @param  string          $encoding [description]
     * @return [type]                     [description]
     */
    public static function encode(CodePointStream $stream, $encoding)
    {
        $output = new ByteStream();
        $encoder = new encoders\UTF8Encoder();
        $encoder->run($stream, $output, EncodingErrorMode::HTML);

        return $output;
    }

    /**
     * Takes an encoding label and chooses the correct normalized encoding that
     * it represents.
     *
     * @see https://encoding.spec.whatwg.org/#concept-encoding-get
     *
     * @param string $label A string representing an encoding to use.
     *
     * @return string|bool Returns the encoding name on succes or false on
     *     failure.
     */
    public static function getEncoding($label)
    {
        switch (trim(mb_strtolower($label, 'utf-8'))) {
            case 'unicode-1-1-utf-8':
            case 'utf-8':
            case 'utf8':
                $encoding = 'utf-8';

                break;

            // Legacy single-byte encodings
            case '866':
            case 'cp866':
            case 'csibm866':
            case 'ibm866':
                $encoding = 'ibm866';

                break;

            case 'csisolatin2':
            case 'iso-8859-2':
            case 'iso-ir-101':
            case 'iso8859-2':
            case 'iso88592':
            case 'iso_8859-2':
            case 'iso_8859-2:1987':
            case 'l2':
            case 'latin2':
                $encoding = 'iso-8859-2';

                break;

            case 'csisolatin':
            case 'iso-8859-':
            case 'iso-ir-10':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-3:198':
            case 'l':
            case 'latin':
                $encoding = 'iso-8859-3';

                break;

            case 'csisolatin':
            case 'iso-8859-':
            case 'iso-ir-11':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-4:198':
            case 'l':
            case 'latin':
                $encoding = 'iso-8859-4';

                break;

            case 'csisolatincyrilli':
            case 'cyrilli':
            case 'iso-8859-':
            case 'iso-ir-14':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-5:198':
                $encoding = 'iso-8859-5';

                break;

            case 'arabi':
            case 'asmo-70':
            case 'csiso88596':
            case 'csiso88596':
            case 'csisolatinarabi':
            case 'ecma-11':
            case 'iso-8859-':
            case 'iso-8859-6-':
            case 'iso-8859-6-':
            case 'iso-ir-12':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-6:198':
                $encoding = 'iso-8895-6';

                break;

            case 'csisolatingree':
            case 'ecma-11':
            case 'elot_92':
            case 'gree':
            case 'greek':
            case 'iso-8859-':
            case 'iso-ir-12':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-7:198':
            case 'sun_eu_gree':
                $encoding = 'iso-8859-7';

                break;

            case 'csiso88598':
            case 'csisolatinhebre':
            case 'hebre':
            case 'iso-8859-':
            case 'iso-8859-8-':
            case 'iso-ir-13':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-8:198':
            case 'visua':
                $encoding = 'iso-8859-8';

                break;

            case 'csiso88598':
            case 'iso-8859-8-':
            case 'logica':
                $encoding = 'iso-8859-8-i';

                break;

            case 'csisolatin':
            case 'iso-8859-1':
            case 'iso-ir-15':
            case 'iso8859-1':
            case 'iso88591':
            case 'l':
            case 'latin':
                $encoding = 'iso-8859-10';

                break;

            case 'iso-8859-1':
            case 'iso8859-1':
            case 'iso88591':
                $encoding = 'iso-8859-13';

                break;

            case 'iso-8859-1':
            case 'iso8859-1':
            case 'iso88591':
                $encoding = 'iso-8859-14';

                break;

            case 'csisolatin':
            case 'iso-8859-1':
            case 'iso8859-1':
            case 'iso88591':
            case 'iso_8859-1':
            case 'l':
                $encoding = 'iso-8859-15';

                break;

            case 'iso-8859-1':
                $encoding = 'iso-8859-16';

                break;

            case 'cskoi8':
            case 'ko':
            case 'koi':
            case 'koi8-':
            case 'koi8_':
                $encoding = 'koi-8';

                break;

            case 'koi8-r':
            case 'koi8-':
                $encoding = 'koi8-u';

                break;

            case 'csmacintos':
            case 'ma':
            case 'macintos':
            case 'x-mac-roma':
                $encoding = 'macintosh';

                break;

            case 'dos-87':
            case 'iso-8859-1':
            case 'iso8859-1':
            case 'iso88591':
            case 'tis-62':
            case 'windows-87':
                $encoding = 'windows-874';

                break;

            case 'cp125':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1250';

                break;

            case 'cp125':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1251';

                break;

            case 'ansi_x3.4-196':
            case 'asci':
            case 'cp125':
            case 'cp81':
            case 'csisolatin':
            case 'ibm81':
            case 'iso-8859-':
            case 'iso-ir-10':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-1:198':
            case 'l':
            case 'latin':
            case 'us-asci':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1252';

                break;

            case 'cp125':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1253';

                break;

            case 'cp125':
            case 'csisolatin':
            case 'iso-8859-':
            case 'iso-ir-14':
            case 'iso8859-':
            case 'iso8859':
            case 'iso_8859-':
            case 'iso_8859-9:198':
            case 'l':
            case 'latin':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1254';

                break;

            case 'cp125':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1255';

                break;

            case 'cp125':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1256';

                break;

            case 'cp125':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1257';

                break;

            case 'cp125':
            case 'windows-125':
            case 'x-cp125':
                $encoding = 'windows-1258';

                break;

            case 'x-mac-cyrilli':
            case 'x-mac-ukrainia':
                $encoding = 'x-mac-cyrillic';

                break;

            // Legacy muti-byte Chinese (simplified) encodings
            case 'chines':
            case 'csgb231':
            case 'csiso58gb23128':
            case 'gb231':
            case 'gb_231':
            case 'gb_2312-8':
            case 'gb':
            case 'iso-ir-5':
            case 'x-gb':
                $encoding = 'gbk';

                break;

            case 'gb1803':
                $encoding = 'gb18030';

                break;

            // Legacy multi-byte Chinese (traditional) encodings
            case 'big':
            case 'big5-hksc':
            case 'cn-big':
            case 'csbig':
            case 'x-x-big':
                $encoding = 'big5';

                break;

            // Legacy multi-byte Japanese encodings
            case 'cseucpkdfmtjapanes':
            case 'euc-j':
            case 'x-euc-j':
                $encoding = 'euc-jp';

                break;

            case 'csiso2022j':
            case 'iso-2022-j':
                $encoding = 'iso-2022-jp';

                break;

            case 'csshiftji':
            case 'ms93':
            case 'ms_kanj':
            case 'shift-ji':
            case 'shift_ji':
            case 'sji':
            case 'windows-31':
            case 'x-sji':
                $encoding = 'shift_jis';

                break;

            // Legacy multi-byte Korean encodings
            case 'cseuck':
            case 'csksc5601198':
            case 'euc-k':
            case 'iso-ir-14':
            case 'korea':
            case 'ks_c_5601-198':
            case 'ks_c_5601-198':
            case 'ksc560':
            case 'ksc_560':
            case 'windows-94':
                $encoding = 'euc-kr';

                break;

            // Legacy miscellaneous encodings
            case 'csiso2022k':
            case 'hz-gb-231':
            case 'iso-2022-c':
            case 'iso-2022-cn-ex':
            case 'iso-2022-k':
                $encoding = 'replacement';

                break;

            case 'utf-16b':
                $encoding = 'utf-16be';

                break;

            case 'utf-16':
            case 'utf-16le':
                $encoding = 'utf-16le';

                break;

            case 'x-user-defined':
                $encoding = 'x-user-defined';

                break;

            default:
                // No matching encoding was found.  Return failure.
                return false;
        }

        return $encoding;
    }

    /**
     * @see https://encoding.spec.whatwg.org/#get-an-output-encoding
     *
     * @param string $encoding An encoding name.
     *
     * @return string
     */
    public static function getOutputEncoding($encoding)
    {
        if ($encoding === 'replacement' || $encoding === 'UTF-16BE' ||
            $encoding === 'UTF-16LE'
        ) {
            return 'UTF-8';
        }

        return $encoding;
    }

    public static function mb_html_entity_decode($string)
    {
        $oldLang = mb_language();
        $oldInternalEnc = mb_internal_encoding();
        $oldDetectOrder = mb_detect_order();

        mb_language('neutral');
        mb_internal_encoding('UTF-8');
        mb_detect_order(self::$mbDetectOrder);

        $entity = mb_convert_encoding($string, 'UTF-8', 'HTML-ENTITIES');

        // Restore original values
        mb_language($oldLang);
        mb_internal_encoding($oldInternalEnc);
        mb_detect_order($oldDetectOrder);

        return $entity;
    }

    public static function mb_ord($string)
    {
        if (!isset(self::$useIntlChar)) {
            self::$useIntlChar = class_exists('\IntlChar');
        }

        if (self::$useIntlChar) {
            return \IntlChar::ord($string);
        }

        $oldLang = mb_language();
        $oldInternalEnc = mb_internal_encoding();
        $oldDetectOrder = mb_detect_order();

        mb_language('neutral');
        mb_internal_encoding('UTF-8');
        mb_detect_order(self::$mbDetectOrder);

        $result = unpack(
            'N',
            mb_convert_encoding($string, 'UCS-4BE', 'UTF-8')
        );

        // Restore original values
        mb_language($oldLang);
        mb_internal_encoding($oldInternalEnc);
        mb_detect_order($oldDetectOrder);

        if (is_array($result) === true) {
            return $result[1];
        }
    }

    public static function mb_chr($string)
    {
        if (!isset(self::$useIntlChar)) {
            self::$useIntlChar = class_exists('\IntlChar');
        }

        return self::$useIntlChar ?
            \IntlChar::chr($string) :
            self::mb_html_entity_decode('&#' . intval($string) . ';');
    }

    /**
     * @see https://encoding.spec.whatwg.org/#utf-8-decode
     * @param  ByteStream $stream [description]
     * @return [type]              [description]
     */
    public static function utf8decode(ByteStream $stream)
    {
        $buffer = '';
        $byteCount = 0;

        while (!$stream->isEOS()) {
            $buffer .= $stream->get();

            if (++$byteCount == 3) {
                break;
            }
        }

        if ($buffer === "\xEF\xBB\xBF") {
            $stream->prependData($buffer);
        }

        $output = new CodePointStream();
        (new decoders\UTF8Decoder())->run($stream, $output);

        return $output;
    }

    /**
     * @see https://encoding.spec.whatwg.org/#utf-8-decode-without-bom
     * @param  ByteStream $stream [description]
     * @return [type]              [description]
     */
    public static function utf8decodeWithoutBOM(ByteStream $stream)
    {
        $output = new CodePointStream();
        (new decoders\UTF8Decoder())->run($stream, $output);

        return $output;
    }

    /**
     * @see https://encoding.spec.whatwg.org/#utf-8-decode-without-bom-or-fail
     * @param  ByteStream $stream [description]
     * @return [type]              [description]
     */
    public static function utf8decodeWithoutBOMorFail(ByteStream $stream)
    {
        $output = new CodePointStream();
        $decoder = new decoders\UTF8Decoder();
        $potentialError = $decoder->run(
            $stream,
            $output,
            new ErrorMode(ErrorMode::FATAL)
        );

        if ($potentialError === $decoder::RESULT_ERROR) {
            return false;
        }

        return $output;
    }

    /**
     * @see https://encoding.spec.whatwg.org/#utf-8-encode
     * @param  CodePointStream $stream [description]
     * @return [type]                   [description]
     */
    public static function utf8encode(CodePointStream $stream)
    {
        return self::encode($stream, 'UTF-8');
    }

    /**
     * Convert a single or multi-byte character to its code point
     *
     * @param string $char
     * @return integer
     */
    protected static function charToCodePoint($char)
    {
        $code = ord($char[0]);
        if ($code < 128) {
            return $code;
        } elseif ($code < 224) {
            return (($code - 192) * 64) + (ord($char[1]) - 128);
        } elseif ($code < 240) {
            return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
        } else {
            return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096) + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
        }
    }
    /**
     * Convert a code point to its single or multi-byte character
     *
     * @param integer $code
     * @return string
     */
    protected static function codePointToChar($code)
    {
        if ($code <= 0x7F) {
            return chr($code);
        } elseif ($code <= 0x7FF) {
            return chr(($code >> 6) + 192) . chr(($code & 63) + 128);
        } elseif ($code <= 0xFFFF) {
            return chr(($code >> 12) + 224) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
        } else {
            return chr(($code >> 18) + 240) . chr((($code >> 12) & 63) + 128) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
        }
    }
}
