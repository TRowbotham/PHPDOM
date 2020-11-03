<?php

declare(strict_types=1);

namespace Rowbot\DOM\Encoding;

use function chr;
use function mb_strtolower;
use function trim;

final class EncodingUtils
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Takes a Unicode code point and encodes it. The return behavior is undefined if the given
     * code point is outside the range 0..10FFFF.
     *
     * @see https://encoding.spec.whatwg.org/#utf-8-encoder
     */
    public static function encodeCodePoint(int $codePoint): string
    {
        if ($codePoint >= 0x00 && $codePoint <= 0x7F) {
            return chr($codePoint);
        }

        $count = 0;
        $offset = 0;

        if ($codePoint >= 0x0080 && $codePoint <= 0x07FF) {
            $count = 1;
            $offset = 0xC0;
        } elseif ($codePoint >= 0x0800 && $codePoint <= 0xFFFF) {
            $count = 2;
            $offset = 0xE0;
        } elseif ($codePoint >= 0x10000 && $codePoint <= 0x10FFFF) {
            $count = 3;
            $offset = 0xF0;
        }

        $bytes = chr(($codePoint >> (6 * $count)) + $offset);

        while ($count > 0) {
            $temp = $codePoint >> (6 * ($count - 1));
            $bytes .= chr(0x80 | ($temp & 0x3F));
            --$count;
        }

        return $bytes;
    }

    /**
     * Takes an encoding label and chooses the correct normalized encoding that
     * it represents.
     *
     * @see https://encoding.spec.whatwg.org/#concept-encoding-get
     *
     * @param string $label A string representing an encoding to use.
     *
     * @return string|bool Returns the encoding name on succes or false on failure.
     */
    public static function getEncoding(string $label)
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
}
