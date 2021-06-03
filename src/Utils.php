<?php

declare(strict_types=1);

namespace Rowbot\DOM;

use function mb_strlen;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;

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
     * @see https://dom.spec.whatwg.org/#converted-to-ascii-uppercase
     */
    public static function toASCIILowercase(string $value): string
    {
        $len = mb_strlen($value, 'utf-8');
        $output = '';

        for ($i = 0; $i < $len; $i++) {
            $codePoint = mb_substr($value, $i, 1, 'utf-8');

            if ($codePoint >= "\x41" && $codePoint <= "\x5A") {
                $output .= mb_strtolower($codePoint, 'utf-8');
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
     */
    public static function toASCIIUppercase(string $value): string
    {
        $len = mb_strlen($value, 'utf-8');
        $output = '';

        for ($i = 0; $i < $len; $i++) {
            $codePoint = mb_substr($value, $i, 1, 'utf-8');

            if ($codePoint >= "\x61" && $codePoint <= "\x7A") {
                $output .= mb_strtoupper($codePoint, 'utf-8');
            } else {
                $output .= $codePoint;
            }
        }

        return $output;
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
