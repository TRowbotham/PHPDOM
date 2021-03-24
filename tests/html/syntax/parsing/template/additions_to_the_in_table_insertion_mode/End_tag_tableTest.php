<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_table_insertion_mode;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-table-insertion-mode/end-tag-table.html
 */
class End_tag_tableTest extends TestCase
{
    use CommonTrait;

    public function testIgnoreTableEndTagToken(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<table id="table">'
            . '<template id="template">'
            . '</table>'
            . '</template>'
            . '<tr><td></td></tr>'
            . '</table>';

        // $table = $doc->querySelector('#table');
        // $template = $table->querySelector('#template');
        $table = $doc->getElementById('table');
        $template = $doc->getElementById('template');

        self::assertSame(2, $table->childNodes->length);
        self::assertNotNull($template);
        self::assertSame(1, $table->rows->length);
        self::assertSame(0, $template->childNodes->length);
        self::assertSame(0, $template->content->childNodes->length);
    }
}
