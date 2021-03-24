<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\clearing_the_stack_back_to_a_given_context;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/clearing-the-stack-back-to-a-given-context/clearing-stack-back-to-a-table-context.html
 */
class Clearing_stack_back_to_a_table_contextTest extends TestCase
{
    use CommonTrait;

    /**
     * @dataProvider contextProvider
     */
    public function testClearing(
        string $description,
        HTMLDocument $doc,
        string $templateInnerHTML,
        string $id,
        string $tagName,
        ?int $bodiesNum = null,
        bool $footerIsNull = false,
        bool $headerIsNull = false
    ): void
    {
        $doc->body->innerHTML = ''
            . '<table id="tbl">'
            . '<template id="tmpl1">'
            // When parser meets <caption>, <colgroup>, <tbody>, <tfoot>, <thead>, <col>
            // stack must be cleared back to table context.
            //But <template> tag should abort this process
            . $templateInnerHTML
            . '</template>'
            . '<tr id="tr">'
            . '<td id="td">'
            . '</td>'
            . '</tr>'
            . '</table>';

        // $table = $doc->querySelector('#tbl');
        // $tr = $doc->querySelector('#tr');
        // $td = $doc->querySelector('#td');
        // $template = $doc->querySelector('#tmpl1');
        $table = $doc->getElementById('tbl');
        $tr = $doc->getElementById('tr');
        $td = $doc->getElementById('td');
        $template = $doc->getElementById('tmpl1');

        self::assertSame(1, $table->rows->length);
        self::assertSame(1, $table->rows[0]->cells->length);
        self::assertSame($table, $template->parentNode);
        // self::assertNotNull($template->content->querySelector('#' . $id));
        self::assertNotNull($template->content->getElementById($id));
        // self::assertSame($tagName, $template->content->querySelector('#' . $id)->tagName);
        self::assertSame($tagName, $template->content->getElementById($id)->tagName);

        if ($bodiesNum !== null) {
            self::assertSame($bodiesNum, $table->tBodies->length);
        }

        if ($footerIsNull) {
            self::assertNull($table->tFoot);
        }

        if ($headerIsNull) {
            self::assertNull($table->tHead);
        }
    }

    public function contextProvider(): array
    {
        $doc = $this->newHTMLDocument();

        return [
            ['Clearing stack back to a table context. Test <caption>',
             $doc, '<caption id="caption1">Table caption</caption>', 'caption1', 'CAPTION'],

            ['Clearing stack back to a table context. Test <colgroup>',
             $doc, '<colgroup id="colgroup1" width="100%"/>', 'colgroup1', 'COLGROUP'],

            ['Clearing stack back to a table context. Test <tbody>',
             $doc, '<tbody id="tbody1"></tbody>', 'tbody1', 'TBODY', 1],

            ['Clearing stack back to a table context. Test <tfoot>',
             $doc, '<tfoot id="tfoot1"></tfoot>', 'tfoot1', 'TFOOT', null, true],

            ['Clearing stack back to a table context. Test <thead>',
             $doc, '<thead id="thead1"></thead>', 'thead1', 'THEAD', null, false, true],

            ['Clearing stack back to a table context. Test <col>',
             $doc, '<col id="col1" width="100%"/>', 'col1', 'COL']
        ];
    }
}
