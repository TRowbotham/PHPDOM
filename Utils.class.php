<?php
namespace phpjs;

class Utils
{
    /**
     * Tries to return a string representation of the variable, however, it may
     * return the original value if none of the conditions are met.
     *
     * @param mixed $aValue A variable to be converted to a string.
     *
     * @param bool $aTreatNullAsEmptyString Whether or not a null value should
     *    be represented as an empty string or represented as the literal string
     *    "null".
     *
     * @param bool $aNullable If true, null is an accepted value and it will
     *     return null instead of a string and it will take precedence
     *     over the $aTreatNullAsEmptyString argument.
     *
     * @return mixed
     */
    public static function DOMString(
        $aValue,
        $aTreatNullAsEmptyString = false,
        $aNullable = false
    ) {
        if (is_string($aValue)) {
            return $aValue;
        } elseif (is_bool($aValue)) {
            return $aValue ? 'true' : 'false';
        } elseif ($aValue === null) {
            if ($aNullable) {
                return null;
            } elseif ($aTreatNullAsEmptyString) {
                return '';
            } else {
                return 'null';
            }
        } elseif (is_scalar($aValue)) {
            return (string) $aValue;
        } elseif (is_object($aValue)) {
            if (method_exists($aValue, '__toString')) {
                return (string) $aValue;
            } else {
                return '[object ' . get_class($aValue) . ']';
            }
        }

        return $aValue;
    }

    /**
     * Replaces all characters in the range U+0041 to U+005A, inclusive, with
     * the corresponding characters in the range U+0061 to U+007A, inclusive.
     *
     * @see https://dom.spec.whatwg.org/#converted-to-ascii-uppercase
     *
     * @param string $aValue A string.
     *
     * @return string
     */
    public static function toASCIILowercase($aValue)
    {
        $len = mb_strlen($aValue);
        $output = '';

        for ($i = 0; $i < $len; $i++) {
            $codePoint = mb_substr($aValue, $i, 1);

            if ($codePoint >= "\x41" && $codePoint <= "\x5A") {
                $output .= mb_strtolower($codePoint);
            } else {
                $output .= $codePoint;
            }
        }

        return $output;
    }

    /**
     * Replaces all characters in the range U+0061 to U+007A, inclusive, with
     * the corresponding characters in the range U+0041 to U+005A, inclusive.
     *
     * @see https://dom.spec.whatwg.org/#converted-to-ascii-lowercase
     *
     * @param string $aValue A string.
     *
     * @return string
     */
    public static function toASCIIUppercase($aValue)
    {
        $len = mb_strlen($aValue);
        $output = '';

        for ($i = 0; $i < $len; $i++) {
            $codePoint = mb_substr($aValue, $i, 1);

            if ($codePoint >= "\x61" && $codePoint <= "\x7A") {
                $output .= mb_strtoupper($codePoint);
            } else {
                $output .= $codePoint;
            }
        }

        return $output;
    }

    public static function intAsString($aValue)
    {
        if (is_string($aValue) && is_int(intval($aValue))) {
            return $aValue;
        } elseif (is_int($aValue)) {
            return (string)$aValue;
        } else {
            return false;
        }
    }

    public static function toInt($aValue)
    {
        if (is_string($aValue) && is_int($temp = intval($aValue))) {
            return $temp;
        } elseif (is_int($aValue)) {
            return $aValue;
        } else {
            return false;
        }
    }

    public static function parseFloatingPointNumber($aInput)
    {
        $position = 0;
        $value = 1;
        $divisor = 1;
        $exponent = 1;
        $length = strlen($aInput);

        self::collectCodePointSequence($aInput, $position, '/\s/');

        if ($position >= $length) {
            return false;
        }

        if ($aInput[$position] == '-') {
            $value = -1;
            $divisor = -1;

            if (++$position >= $length) {
                return false;
            }
        } elseif ($aInput[$position] == '+') {
            if (++$position >= $length) {
                return false;
            }
        }

        if ($aInput[$position] == '.' && ($position + 1) < ($length - 1) &&
            preg_match('/\d/', $aInput[$position + 1])) {
            $value = 0;
            goto Fraction;
        }

        if (!preg_match('/\d/', $aInput[$position])) {
            return false;
        }

        $value *= intval(
            self::collectCodePointSequence(
                $aInput,
                $position,
                '/\d/'
            )
        );

        if ($position >= $length) {
            goto Conversion;
        }

        Fraction:
        if ($aInput[$position] == '.') {
            // TODO: Finish
        }

        Conversion:
    }

    /**
     * @see https://html.spec.whatwg.org/#rules-for-parsing-integers
     *
     * @param  [type] $aInput [description]
     * @return [type]         [description]
     */
    public static function parseSignedInt($aInput)
    {
        $position = 0;
        $sign = 'positive';
        $length = strlen($aInput);

        self::collectCodePointSequence($aInput, $position, '/\s/');

        if ($position >= $length) {
            return false;
        }

        if ($aInput[$position] == '-') {
            $sign = 'negative';

            if (++$position >= $length) {
                return false;
            }
        } elseif ($aInput[$position] == '+') {
            if (++$position >= $length) {
                return false;
            }
        }

        if (!preg_match('/\d/', $aInput[$position])) {
            return false;
        }

        $value = intval(
            self::collectCodePointSequence(
                $aInput,
                $position,
                '/\d/'
            ),
            10
        );

        return $sign == 'positive' ? $value : 0 - $value;
    }

    /**
     * @see https://html.spec.whatwg.org/#rules-for-parsing-non-negative-integers
     * @param  [type] $aInput [description]
     * @return [type]         [description]
     */
    public static function parseNonNegativeInt($aInput)
    {
        $value = self::parseSignedInt($aInput);

        if ($value === false) {
            return false;
        }

        if ($value < 0) {
            return false;
        }

        return $value;
    }

    /**
     * Takes an input string and then parses the string for tokens while
     * skipping over whitespace.
     *
     * @see https://dom.spec.whatwg.org/#concept-ordered-set-parser
     *
     * @param string $aInput A space delimited string of tokens to be parsed.
     *
     * @return string[] Array containing the parsed tokens.
     */
    public static function parseOrderedSet($aInput)
    {
        $position = 0;
        $tokens = [];
        $length = mb_strlen($aInput);

        self::collectCodePointSequence($aInput, $position, '/\s/');

        while ($position < $length) {
            $token = self::collectCodePointSequence($aInput, $position, '/\S/');

            if (!isset($tokens[$token])) {
                $tokens[$token] = 1;
            }

            self::collectCodePointSequence($aInput, $position, '/\s/');
        }

        return array_keys($tokens);
    }

    /**
     * Parses a string for a token and returns that token.
     *
     * @see https://dom.spec.whatwg.org/#collect-a-code-point-sequence
     *
     * @param string $aInput String of tokens to be parsed.
     *
     * @param int &$aPosition Current position in the token string.
     *
     * @param string $aPattern A regular expresion representing a set of
     *     characers that should be collected.
     *
     * @return string Concatenated list of characters.
     */
    public static function collectCodePointSequence(
        $aInput,
        &$aPosition,
        $aPattern
    ) {
        $result = '';
        $length = mb_strlen($aInput);

        while ($aPosition < $length) {
            $c = mb_substr($aInput, $aPosition, 1);

            if (!preg_match($aPattern, $c)) {
                break;
            }

            $result .= $c;
            $aPosition++;
        }

        return $result;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/infrastructure.html#strictly-split-a-string
     * @param  string   $aInput     [description]
     * @param  string   $aDelimiter [description]
     * @return string[]             [description]
     */
    public function strictlySplitString($aInput, $aDelimiter)
    {
        $position = 0;
        $tokens = [];
        $length = mb_strlen($aInput);

        while ($position < $length) {
            $token = self::collectCodePointSequence(
                $aInput,
                $position,
                '/[^\x20]/'
            );

            if ($token) {
                $tokens[] = $token;
            }

            $position++;
        }

        return $tokens;
    }

    /**
     * Takes an array and concatenates the values of the array into a string
     * with each token separated by U+0020.
     *
     * @see https://dom.spec.whatwg.org/#concept-ordered-set-serializer
     *
     * @param string[] $aSet An ordered set of tokens.
     *
     * @return string Concatenated string of tokens.
     */
    public static function serializeOrderedSet($aSet, $aIsAssoc = false)
    {
        if ($aIsAssoc) {
            $count = 0;
            $set = '';

            foreach ($aSet as $key => $value) {
                if ($count++ != 0) {
                    $set .= "\x20";
                }

                $set .= $key;
            }

            return $set;
        }

        return implode("\x20", $aSet);
    }
}
