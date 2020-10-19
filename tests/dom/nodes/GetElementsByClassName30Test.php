<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/getElementsByClassName-30.htm
 */
class GetElementsByClassName30Test extends NodeTestCase
{
    use WindowTrait;

    private const ARR_ELEMENTS = [
        "HEAD",
        "TITLE",
        "META",
        "LINK",
        "BASE",
        "SCRIPT",
        "STYLE",
        "BODY",
        "A",
        "ABBR",
        "ACRONYM",
        "ADDRESS",
        "APPLET",
        "B",
        "BDO",
        "BIG",
        "BLOCKQUOTE",
        "BR",
        "BUTTON",
        "CENTER",
        "CITE",
        "CODE",
        "DEL",
        "DFN",
        "DIR",
        "LI",
        "DIV",
        "DL",
        "DT",
        "DD",
        "EM",
        "FONT",
        "FORM",
        "LABEL",
        "FIELDSET",
        "LEGEND",
        "H1",
        "HR",
        "I",
        "IFRAME",
        "IMG",
        "INPUT",
        "INS",
        "KBD",
        "MAP",
        "AREA",
        "MENU",
        "NOSCRIPT",
        "OBJECT",
        "PARAM",
        "OL",
        "P",
        "PRE",
        "Q",
        "S",
        "SAMP",
        "SELECT",
        "OPTGROUP",
        "OPTION",
        "SMALL",
        "SPAN",
        "STRIKE",
        "STRONG",
        "SUB",
        "SUP",
        "TABLE",
        "CAPTION",
        "COL",
        "COLGROUP",
        "THEAD",
        "TH",
        "TBODY",
        "TR",
        "TD",
        "TFOOT",
        "TEXTAREA",
        "TT",
        "U",
        "UL",
        "VAR",
    ];

    public function testGetElementsByClassName(): void
    {
        $document = self::getWindow()->document;
        $collection = $document->getElementsByClassName("foo");
        $length = $collection->length;

        for ($x = 0; $x < $length; ++$x) {
            self::assertSame(self::ARR_ELEMENTS[$x], $collection[$x]->nodeName);
        }
    }

    public static function getDocumentName(): string
    {
        return 'getElementsByClassName-30.html';
    }
}
