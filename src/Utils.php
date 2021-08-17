<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use function strtr;

final class Utils
{
    /**
     * @see https://infra.spec.whatwg.org/#ascii-whitespace
     */
    public const ASCII_WHITESPACE = '/[\x09\x0A\x0C\x0D\x20]+/u';

    private const TO_LOWERCASE_CHAR_MAP = [
        'A' => 'a',
        'B' => 'b',
        'C' => 'c',
        'D' => 'd',
        'E' => 'e',
        'F' => 'f',
        'G' => 'g',
        'H' => 'h',
        'I' => 'i',
        'J' => 'j',
        'K' => 'k',
        'L' => 'l',
        'M' => 'm',
        'N' => 'n',
        'O' => 'o',
        'P' => 'p',
        'Q' => 'q',
        'R' => 'r',
        'S' => 's',
        'T' => 't',
        'U' => 'u',
        'V' => 'v',
        'W' => 'w',
        'X' => 'x',
        'Y' => 'y',
        'Z' => 'z',
    ];

    private const TO_UPPERCASE_CHAR_MAP = [
        'a' => 'A',
        'b' => 'B',
        'c' => 'C',
        'd' => 'D',
        'e' => 'E',
        'f' => 'F',
        'g' => 'G',
        'h' => 'H',
        'i' => 'I',
        'j' => 'J',
        'k' => 'K',
        'l' => 'L',
        'm' => 'M',
        'n' => 'N',
        'o' => 'O',
        'p' => 'P',
        'q' => 'Q',
        'r' => 'R',
        's' => 'S',
        't' => 'T',
        'u' => 'U',
        'v' => 'V',
        'w' => 'W',
        'x' => 'X',
        'y' => 'Y',
        'z' => 'Z',
    ];

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Replaces all characters in the range U+0041 to U+005A, inclusive, with
     * the corresponding characters in the range U+0061 to U+007A, inclusive.
     *
     * @see https://infra.spec.whatwg.org/#ascii-lowercase
     */
    public static function toASCIILowercase(string $value): string
    {
        return strtr($value, self::TO_LOWERCASE_CHAR_MAP);
    }

    /**
     * Replaces all characters in the range U+0061 to U+007A, inclusive, with
     * the corresponding characters in the range U+0041 to U+005A, inclusive.
     *
     * @see https://infra.spec.whatwg.org/#ascii-uppercase
     */
    public static function toASCIIUppercase(string $value): string
    {
        return strtr($value, self::TO_UPPERCASE_CHAR_MAP);
    }

    public static function unsignedLong(int $offset): int
    {
        $normalizedOffset = $offset % (2 ** 32);

        if ($normalizedOffset < 0) {
            $normalizedOffset += 2 ** 32;
        }

        return $normalizedOffset;
    }
}
