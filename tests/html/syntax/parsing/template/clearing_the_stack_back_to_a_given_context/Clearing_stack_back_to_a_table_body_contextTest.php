<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\clearing_the_stack_back_to_a_given_context;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/clearing-the-stack-back-to-a-given-context/clearing-stack-back-to-a-table-body-context.html
 */
class Clearing_stack_back_to_a_table_body_contextTest extends TestCase
{
    use CommonTrait;

    /**
     * @dataProvider contextProvider
     */
    public function testClearing(
        string $description,
        HTMLDocument $doc,
        string $tagToTest,
        string $templateInnerHTML,
        ?string $id,
        ?string $tagName,
        ?int $bodiesNum = null,
        bool $footerIsNull = false,
        ?string $footerId = null,
        bool $headerIsNull = false,
        ?string $headerId = null
    ): void
    {
        $doc->body->innerHTML = ''
            . '<table id="tbl">'
            . '<' . $tagToTest . '>'
            . '<template id="tmpl1">'
            // When parser meets <tr>, </tbody>, </tfoot>, </thead>, <caption>, <col>,
            // <colgroup>, <tbody>, <tfoot>, <thead>, </table>
            // stack must be cleared back to table body context. But <template> tag should
            // abort this
            . $templateInnerHTML
            . '</template>'
            . '<tr id="tr">'
            . '<td id="td">'
            . '</td>'
            . '</tr>'
            . '</' . $tagToTest . '>'
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

        if ($id !== null) {
            // self::assertNotNull($template->content->querySelector('#' . $id));
            self::assertNotNull($template->content->getElementById($id));
        }

        if ($tagName !== null) {
            // self::assertSame($tagName, $template->content->querySelector('#' . $id)->tagName);
            self::assertSame($tagName, $template->content->getElementById($id)->tagName);
        }

        self::assertNull($table->caption);

        if ($bodiesNum !== null) {
            self::assertSame($bodiesNum, $table->tBodies->length);
        }

        if ($footerIsNull) {
            self::assertNull($table->tFoot);
        }

        if ($footerId) {
            self::assertNotSame($footerId, $table->tFoot->id);
        }

        if ($headerIsNull) {
            self::assertNull($table->tHead);
        }

        if ($headerId) {
            self::assertNotSame($headerId, $table->tHead->id);
        }
    }

    public function contextProvider(): array
    {
        $doc = $this->newHTMLDocument();

        return [
            ['Clearing stack back to a table body context. Test <tr> in <tbody>',
             $doc, 'tbody', '<tr id="tr1"><td>Cell content</td></tr>', 'tr1', 'TR'],

            ['Clearing stack back to a table body context. Test <tr> in <thead>',
             $doc, 'thead', '<tr id="tr2"><td>Cell content</td></tr>', 'tr2', 'TR'],

            ['Clearing stack back to a table body context. Test <tr> in <tfoot>',
             $doc, 'tfoot', '<tr id="tr3"><td>Cell content</td></tr>', 'tr3', 'TR'],

            ['Clearing stack back to a table body context. Test </tbody>',
             $doc, 'tbody', '</tbody>', null, null],

            ['Clearing stack back to a table body context. Test </thead>',
             $doc, 'thead', '</thead>', null, null],

            ['Clearing stack back to a table body context. Test </tfoot>',
             $doc, 'tfoot', '</tfoot>', null, null],

            ['Clearing stack back to a table body context. Test <caption> in <tbody>',
             $doc, 'tbody', '<caption id="caption1">Table Caption</caption>', 'caption1', 'CAPTION'],

            ['Clearing stack back to a table body context. Test <caption> in <tfoot>',
             $doc, 'tfoot', '<caption id="caption2">Table Caption</caption>', 'caption2', 'CAPTION'],

            ['Clearing stack back to a table body context. Test <caption> in <thead>',
             $doc, 'thead', '<caption id="caption3">Table Caption</caption>', 'caption3', 'CAPTION'],

            ['Clearing stack back to a table body context. Test <col> in <tbody>',
             $doc, 'tbody', '<col id="col1" width="150"/>', 'col1', 'COL'],

            ['Clearing stack back to a table body context. Test <col> in <tfoot>',
             $doc, 'tfoot', '<col id="col2" width="150"/>', 'col2', 'COL'],

            ['Clearing stack back to a table body context. Test <col> in <thead>',
             $doc, 'thead', '<col id="col3" width="150"/>', 'col3', 'COL'],

            ['Clearing stack back to a table body context. Test <colgroup> in <tbody>',
             $doc, 'tbody', '<colgroup id="colgroup1" width="150"/>', 'colgroup1', 'COLGROUP'],

            ['Clearing stack back to a table body context. Test <colgroup> in <tfoot>',
             $doc, 'tfoot', '<colgroup id="colgroup2" width="150"/>', 'colgroup2', 'COLGROUP'],

            ['Clearing stack back to a table body context. Test <colgroup> in <thead>',
             $doc, 'thead', '<colgroup id="colgroup3" width="150"/>', 'colgroup3', 'COLGROUP'],

            ['Clearing stack back to a table body context. Test <tbody> in <tbody>',
             $doc, 'tbody', '<tbody id="tbody1"></tbody>', 'tbody1', 'TBODY', 1],

            ['Clearing stack back to a table body context. Test <tbody> in <tfoot>',
             $doc, 'tfoot', '<tbody id="tbody2"></tbody>', 'tbody2', 'TBODY', 0],

            ['Clearing stack back to a table body context. Test <tbody> in <thead>',
             $doc, 'thead', '<tbody id="tbody3"></tbody>', 'tbody3', 'TBODY', 0],

            ['Clearing stack back to a table body context. Test <tfoot> in <tbody>',
             $doc, 'tbody', '<tfoot id="tfoot1"></tfoot>', 'tfoot1', 'TFOOT', null, true],

            ['Clearing stack back to a table body context. Test <tfoot> in <tfoot>',
             $doc, 'tfoot', '<tfoot id="tfoot2"></tfoot>', 'tfoot2', 'TFOOT', null, false, 'tfoot2'],

            ['Clearing stack back to a table body context. Test <tfoot> in <thead>',
             $doc, 'thead', '<tfoot id="tfoot3"></tfoot>', 'tfoot3', 'TFOOT', null, true],

            ['Clearing stack back to a table body context. Test <thead> in <tbody>',
             $doc, 'tbody', '<thead id="thead1"></thead>', 'thead1', 'THEAD', null, false, null, true],

            ['Clearing stack back to a table body context. Test <thead> in <tfoot>',
             $doc, 'tfoot', '<thead id="thead2"></thead>', 'thead2', 'THEAD', null, false, null, true],

            ['Clearing stack back to a table body context. Test <thead> in <thead>',
             $doc, 'thead', '<thead id="thead3"></thead>', 'thead3', 'THEAD', null, false, null, false, 'thead3'],

            ['Clearing stack back to a table body context. Test </table> in <tbody>',
             $doc, 'tbody', '</table>', null, null, null, false, null, true],

            ['Clearing stack back to a table body context. Test </table> in <tfoot>',
             $doc, 'tfoot', '</table>', null, null, null, false, null, true],

            ['Clearing stack back to a table body context. Test </table> in <thead>',
             $doc, 'thead', '</table>', null, null],

            ['Clearing stack back to a table body context. Test </tbody> in <thead>',
             $doc, 'thead', '</tbody>', null, null],

            ['Clearing stack back to a table body context. Test </tbody> in <tfoot>',
             $doc, 'tfoot', '</tbody>', null, null],

            ['Clearing stack back to a table body context. Test </thead> in <tbody>',
             $doc, 'tbody', '</thead>', null, null],

            ['Clearing stack back to a table body context. Test </thead> in <tfoot>',
             $doc, 'tfoot', '</thead>', null, null],

            ['Clearing stack back to a table body context. Test </tfoot> in <thead>',
             $doc, 'thead', '</tfoot>', null, null],

            ['Clearing stack back to a table body context. Test </tfoot> in <tbody>',
             $doc, 'tbody', '</tfoot>', null, null]
        ];
    }
}
