<?php
namespace Rowbot\DOM;

use function array_keys;
use function get_class;
use function implode;
use function intval;
use function is_bool;
use function is_int;
use function is_object;
use function is_scalar;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function mb_strtolower;
use function mb_strtoupper;
use function method_exists;
use function pow;
use function preg_match;
use function strlen;

final class Utils
{
    /**
     * @see https://infra.spec.whatwg.org/#ascii-whitespace
     *
     * @var string
     */
    public const ASCII_WHITESPACE = '/[\x09\x0A\x0C\x0D\x20]+/u';

    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Returns a string representation of the given value. If the nullable
     * option is true, then this method has a chance to return null, otherwise,
     * it will always return a string. According to the spec, a DOMString
     * should be a UTF-16 encoded string, however, we intentionally skip this at
     * the present time.
     *
     * @param mixed $value                  A variable to be converted to a string.
     * @param bool  $treatNullAsEmptyString Whether or not a null value should be represented as an empty string or
     *                                      represented as the literal string "null".
     * @param bool  $nullable               If true, null is an accepted value and it will return null instead of a
     *                                      string and it will take precedence over the $treatNullAsEmptyString
     *                                      argument.
     *
     * @return ?string
     */
    public static function DOMString(
        $value,
        bool $treatNullAsEmptyString = false,
        bool $nullable = false
    ): ?string {
        if (is_string($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif ($value === null) {
            if ($nullable) {
                return null;
            } elseif ($treatNullAsEmptyString) {
                return '';
            } else {
                return 'null';
            }
        } elseif (is_scalar($value)) {
            return (string) $value;
        } elseif (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            } else {
                return '[object ' . get_class($value) . ']';
            }
        }

        return '';
    }

    /**
     * Replaces all characters in the range U+0041 to U+005A, inclusive, with
     * the corresponding characters in the range U+0061 to U+007A, inclusive.
     *
     * @see https://dom.spec.whatwg.org/#converted-to-ascii-uppercase
     *
     * @param string $value A string.
     *
     * @return string
     */
    public static function toASCIILowercase($value): string
    {
        $len = mb_strlen($value);
        $output = '';

        for ($i = 0; $i < $len; $i++) {
            $codePoint = mb_substr($value, $i, 1);

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
     * @param string $value A string.
     *
     * @return string
     */
    public static function toASCIIUppercase($value): string
    {
        $len = mb_strlen($value);
        $output = '';

        for ($i = 0; $i < $len; $i++) {
            $codePoint = mb_substr($value, $i, 1);

            if ($codePoint >= "\x61" && $codePoint <= "\x7A") {
                $output .= mb_strtoupper($codePoint);
            } else {
                $output .= $codePoint;
            }
        }

        return $output;
    }

    /**
     * @param int $offset
     *
     * @return int
     */
    public static function unsignedLong(int $offset): int
    {
        $normalizedOffset = $offset % pow(2, 32);

        if ($normalizedOffset < 0) {
            $normalizedOffset += pow(2, 32);
        }

        return $normalizedOffset;
    }

    /**
     * @see https://html.spec.whatwg.org/#rules-for-parsing-floating-point-number-values
     *
     * @param string $input
     *
     * @return float|false
     */
    public static function parseFloatingPointNumber(string $input)
    {
        $position = 0;
        $value = 1;
        $divisor = 1;
        $exponent = 1;
        $length = strlen($input);

        self::collectCodePointSequence($input, $position, '/\s/');

        if ($position >= $length) {
            return false;
        }

        if ($input[$position] == '-') {
            $value = -1;
            $divisor = -1;

            if (++$position >= $length) {
                return false;
            }
        } elseif ($input[$position] == '+') {
            if (++$position >= $length) {
                return false;
            }
        }

        if ($input[$position] == '.' && ($position + 1) < ($length - 1) &&
            preg_match('/\d/', $input[$position + 1])) {
            $value = 0;
            goto Fraction;
        }

        if (!preg_match('/\d/', $input[$position])) {
            return false;
        }

        $value *= intval(
            self::collectCodePointSequence(
                $input,
                $position,
                '/\d/'
            )
        );

        if ($position >= $length) {
            goto Conversion;
        }

        Fraction:
        if ($input[$position] == '.') {
            // TODO: Finish
        }

        Conversion:
    }

    /**
     * @see https://html.spec.whatwg.org/#rules-for-parsing-integers
     *
     * @param string $input
     *
     * @return int|false
     */
    public static function parseSignedInt(string $input)
    {
        $position = 0;
        $sign = 'positive';
        $length = strlen($input);

        self::collectCodePointSequence($input, $position, '/\s/');

        if ($position >= $length) {
            return false;
        }

        if ($input[$position] == '-') {
            $sign = 'negative';

            if (++$position >= $length) {
                return false;
            }
        } elseif ($input[$position] == '+') {
            if (++$position >= $length) {
                return false;
            }
        }

        if (!preg_match('/\d/', $input[$position])) {
            return false;
        }

        $value = intval(
            self::collectCodePointSequence(
                $input,
                $position,
                '/\d/'
            ),
            10
        );

        return $sign == 'positive' ? $value : 0 - $value;
    }

    /**
     * @see https://html.spec.whatwg.org/#rules-for-parsing-non-negative-integers
     *
     * @param string $input
     *
     * @return int|false
     */
    public static function parseNonNegativeInt(string $input)
    {
        $value = self::parseSignedInt($input);

        if ($value === false) {
            return false;
        }

        if ($value < 0) {
            return false;
        }

        return $value;
    }

    /**
     * @see https://dom.spec.whatwg.org/#retarget
     *
     * @param \Rowbot\DOM\Node $objectA
     * @param \Rowbot\DOM\Node $objectB
     *
     * @return \Rowbot\DOM\Node
     */
    public static function retargetObject(Node $objectA, Node $objectB): Node
    {
        while (true) {
            $root = $objectA->getRootNode();

            if (!$root instanceof ShadowRoot
                || $root->isShadowIncludingInclusiveAncestorOf($objectB)
            ) {
                return $objectA;
            }

            $objectA = $root->getHost();
        }
    }
}
