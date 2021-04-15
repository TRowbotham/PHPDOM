<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\clearing_the_stack_back_to_a_given_context;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/clearing-the-stack-back-to-a-given-context/clearing-stack-back-to-a-table-row-context.html
 */
class Clearing_stack_back_to_a_table_row_contextTest extends TestCase
{
    use CommonTrait;

    /**
     * @dataProvider contextProvider
     */
    public function testClearing(
        string $description,
        HTMLDocument $doc,
        string $templateInnerHTML,
        ?string $id,
        ?string $tagName,
        ?string $elementId = null
    ): void {
        $doc->body->innerHTML = ''
            . '<table id="tbl">'
            . '<tr id="tr">'
            . '<template id="tmpl1">'
            // When parser meets <th>, <td>, </tr>, stack must be cleared
            // back to table row context.
            // But <template> tag should abort this
            . $templateInnerHTML
            . '</template>'
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
        self::assertSame($tr, $template->parentNode);

        if ($id !== null) {
            // self::assertNotNull($template->content->querySelector('#' . $id));
            self::assertNotNull($template->content->getElementById($id));
        }

        if ($tagName !== null) {
            // self::assertSame($tagName, $template->content->querySelector('#' . $id)->tagName);
            self::assertSame($tagName, $template->content->getElementById($id)->tagName);
        }

        if ($elementId) {
            // self::assertNull($doc->querySelector('#' . $elementId));
            self::assertNull($doc->getElementById($elementId));
        }
    }

    public function contextProvider(): array
    {
        $doc = $this->newHTMLDocument();

        return [
            ['Clearing stack back to a table row context. Test <th>',
             $doc, '<th id="th1">Table header</th>', 'th1', 'TH', 'th1'],

            ['Clearing stack back to a table row context. Test <td>',
             $doc, '<td id="td1">Table cell</td>', 'td1', 'TD', 'td1'],

            ['Clearing stack back to a table row context. Test </tr>',
             $doc, '</tr>', null, null],
        ];
    }
}
