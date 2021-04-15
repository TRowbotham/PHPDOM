<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_foster_parenting;

use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-foster-parenting/template-is-a-foster-parent-element.html
 */
class Template_is_a_foster_parent_elementTest extends TestCase
{
    use CommonTrait;

    public function testTemplateIsAFosterParentElementWithTable(): void
    {
        $doc = $this->newHTMLDocument();

        $doc->body->innerHTML = ''
            . '<div id="tmplParent">'
            . '<template id="tmpl1">'
                . '<table id="tbl">'
                    . '<tr><td>Cell 1</td></tr>'
                  // Misplaced <div>. It should be foster parented
                . '<div id="orphanDiv">Orphan div content</div>'
                    . '<tr><td>Cell 2</td></tr>'
                . '</table>'
            . '</template>'
        . '</div>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        // $div = $template->content->querySelector('#orphanDiv');
        $div = $template->content->getElementById('orphanDiv');

        self::assertSame($template->content, $div->parentNode);
    }

    public function testTemplateIsAFosterParentElementWithoutTable(): void
    {
        $doc = $this->newHTMLDocument();

        $doc->body->innerHTML = ''
            . '<div id="tmplParent">'
            . '<template id="tmpl1">'
                    . '<tr><td>Cell 1</td></tr>'
                  // Misplaced <div>. It should be foster parented
                . '<div id="orphanDiv">Orphan div content</div>'
                    . '<tr><td>Cell 2</td></tr>'
            . '</template>'
        . '</div>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        // $div = $template->content->querySelector('#orphanDiv');
        $div = $template->content->getElementById('orphanDiv');

        self::assertSame($template->content, $div->parentNode);
    }
}
