<?php
declare(strict_types=1);

namespace Rowbot\DOM\Parser\HTML;

/**
 * @see https://html.spec.whatwg.org/multipage/syntax.html#the-insertion-mode
 */
abstract class ParserInsertionMode
{
    const INITIAL              = 1;
    const BEFORE_HTML          = 2;
    const BEFORE_HEAD          = 3;
    const IN_HEAD              = 4;
    const IN_HEAD_NOSCRIPT     = 5;
    const AFTER_HEAD           = 6;
    const IN_BODY              = 7;
    const TEXT                 = 8;
    const IN_TABLE             = 9;
    const IN_TABLE_TEXT        = 10;
    const IN_CAPTION           = 11;
    const IN_COLUMN_GROUP      = 12;
    const IN_TABLE_BODY        = 13;
    const IN_ROW               = 14;
    const IN_CELL              = 15;
    const IN_SELECT            = 16;
    const IN_SELECT_IN_TABLE   = 17;
    const IN_TEMPLATE          = 18;
    const AFTER_BODY           = 19;
    const IN_FRAMESET          = 20;
    const AFTER_FRAMESET       = 21;
    const AFTER_AFTER_BODY     = 22;
    const AFTER_AFTER_FRAMESET = 23;
}
