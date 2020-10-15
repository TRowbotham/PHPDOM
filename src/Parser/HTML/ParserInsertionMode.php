<?php

declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-insertion-mode
 */
final class ParserInsertionMode
{
    public const INITIAL              = 1;
    public const BEFORE_HTML          = 2;
    public const BEFORE_HEAD          = 3;
    public const IN_HEAD              = 4;
    public const IN_HEAD_NOSCRIPT     = 5;
    public const AFTER_HEAD           = 6;
    public const IN_BODY              = 7;
    public const TEXT                 = 8;
    public const IN_TABLE             = 9;
    public const IN_TABLE_TEXT        = 10;
    public const IN_CAPTION           = 11;
    public const IN_COLUMN_GROUP      = 12;
    public const IN_TABLE_BODY        = 13;
    public const IN_ROW               = 14;
    public const IN_CELL              = 15;
    public const IN_SELECT            = 16;
    public const IN_SELECT_IN_TABLE   = 17;
    public const IN_TEMPLATE          = 18;
    public const AFTER_BODY           = 19;
    public const IN_FRAMESET          = 20;
    public const AFTER_FRAMESET       = 21;
    public const AFTER_AFTER_BODY     = 22;
    public const AFTER_AFTER_FRAMESET = 23;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
