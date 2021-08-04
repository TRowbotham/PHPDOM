<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use function implode;
use function mb_str_split;
use function strtolower;
use function strtoupper;

final class Utils
{
    /**
     * @see https://infra.spec.whatwg.org/#ascii-whitespace
     */
    public const ASCII_WHITESPACE = '/[\x09\x0A\x0C\x0D\x20]+/u';

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
        $codePoints = mb_str_split($value, 1, 'utf-8');

        foreach ($codePoints as $i => $codePoint) {
            if ($codePoint >= 'A' && $codePoint <= 'Z') {
                $codePoints[$i] = strtolower($codePoint);
            }
        }

        return implode('', $codePoints);
    }

    /**
     * Replaces all characters in the range U+0061 to U+007A, inclusive, with
     * the corresponding characters in the range U+0041 to U+005A, inclusive.
     *
     * @see https://infra.spec.whatwg.org/#ascii-uppercase
     */
    public static function toASCIIUppercase(string $value): string
    {
        $codePoints = mb_str_split($value, 1, 'utf-8');

        foreach ($codePoints as $i => $codePoint) {
            if ($codePoint >= 'a' && $codePoint <= 'z') {
                $codePoints[$i] = strtoupper($codePoint);
            }
        }

        return implode('', $codePoints);
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
