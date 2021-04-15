<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_head_insertion_mode;

use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-head-insertion-mode/generating-of-implied-end-tags.html
 */
class Generating_of_implied_end_tagsTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testGeneratingOfImpliedEndTagsForTableElements(): void
    {
        $doc = $this->newHTMLDocument();

        //No end </td></tr></table> tags. Should be added implicitly
        $doc->head->innerHTML = '<template id="tpl">'
            . '<table id="tbl"><tr id="tr"><td id="td"></template>';

        // $template = $doc->querySelector('#tpl');
        $template = $doc->getElementById('tpl');

        self::assertNotNull($template);
        // self::assertNull($doc->querySelector('#tbl'));
        // self::assertNull($doc->querySelector('#tr'));
        // self::assertNull($doc->querySelector('#td'));
        self::assertNull($doc->getElementById('tbl'));
        self::assertNull($doc->getElementById('tr'));
        self::assertNull($doc->getElementById('td'));

        // self::assertNotNull($template->content->querySelector('#tbl'));
        // self::assertNotNull($template->content->querySelector('#tr'));
        // self::assertNotNull($template->content->querySelector('#td'));
        self::assertNotNull($template->content->getElementById('tbl'));
        self::assertNotNull($template->content->getElementById('tr'));
        self::assertNotNull($template->content->getElementById('td'));
    }

    public function testGeneratingOfImpliedEndTagsForDivElement(): void
    {
        $doc = $this->newHTMLDocument();

        //No end </div> tag. Should be added implicitly
        $doc->head->innerHTML = '<template id="tpl"><div id="dv">Div content</template>';

        // $template = $doc->querySelector('#tpl');
        $template = $doc->getElementById('tpl');

        self::assertNotNull($template);
        // self::assertNull($doc->querySelector('#dv'));
        self::assertNull($doc->getElementById('dv'));
        // self::assertNotNull($template->content->querySelector('#dv'));
        self::assertNotNull($template->content->getElementById('dv'));
    }

    public function testGeneratingOfImpliedEndTagsForSomeTextAndDivElement(): void
    {
        $doc = $this->newHTMLDocument();

        //No end </div> tag. Should be added implicitly
        $doc->head->innerHTML = '<template id="tpl">Template text<div id="dv">Div content</template>';

        // $template = $doc->querySelector('#tpl');
        $template = $doc->getElementById('tpl');

        self::assertNotNull($template);
        // self::assertNull($doc->querySelector('#dv'));
        self::assertNull($doc->getElementById('dv'));
        // $div = $template->content->querySelector('#dv');
        $div = $template->content->getElementById('dv');
        self::assertNotNull($div);
        self::assertSame('Div content', $div->textContent);
    }

    public function testGeneratingOfImpliedEndTagsForWrongEndTag(): void
    {
        $doc = $this->newHTMLDocument();

        //No end </div> tag. Should be added implicitly
        $doc->head->innerHTML = '<template id="tpl"><div id="dv">Div content</span></template>';

        // $template = $doc->querySelector('#tpl');
        $template = $doc->getElementById('tpl');

        self::assertNotNull($template);
        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNull($doc->querySelector('#dv'));
        self::assertNull($doc->getElementById('dv'));
        // self::assertNotNull($template->content->querySelector('#dv'));
        self::assertNotNull($template->content->getElementById('dv'));
        // self::assertSame('Div content', $template->content->querySelector('#dv')->textContent);
        self::assertSame('Div content', $template->content->getElementById('dv')->textContent);
    }

    public function testGeneratingOfImpliedEndTagsForTableElementsLoadingOfHTMLDocumentFromAFile(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'head-template-contents-table-no-end-tag.html')->contentDocument;

        // $template = $doc->head->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertNotNull($template);

        // self::assertNotNull($template->content->querySelector('table'));
        // self::assertNotNull($template->content->querySelector('tr'));
        // self::assertNotNull($template->content->querySelector('td'));
        self::assertTrue($template->content->firstElementChild->localName === 'table');
        self::assertTrue($template->content->firstElementChild->firstElementChild->firstElementChild->localName === 'tr');
        self::assertTrue($template->content->firstElementChild->firstElementChild->firstElementChild->firstElementChild->localName === 'td');
    }

    public function testGeneratingOfImpliedEndTagsForDivElementLoadingOfHTMLDocumentFromAFile(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'head-template-contents-div-no-end-tag.html')->contentDocument;

        // $template = $doc->head->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertNotNull($template);

        // $div = $template->content->querySelector('div');
        $div = $template->content->firstElementChild;

        self::assertNotNull($div);
        self::assertSame("Hello, template\n    ", $div->textContent);
    }
}
