<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_foster_parenting;

use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-foster-parenting/template-is-not-a-foster-parent-element.html
 */
class Template_is_not_a_foster_parent_elementTest extends TestCase
{
    use CommonTrait;

    public function testTemplateIsNotAFosterParentElementWhenHigherInTheStack(): void
    {
        $doc = $this->newHTMLDocument();

        $doc->body->innerHTML = ''
            . '<div id="tmplParent">'
            . '<template id="tmpl1">'
                . '<div id="fosterParent">'
                    . '<table id="tbl">'
                        . '<tr><td>Cell 1</td></tr>'
                      // Misplaced <div>. It should be foster parented
                    . '<div id="orphanDiv">Orphan div content</div>'
                        . '<tr><td>Cell 2</td></tr>'
                    . '</table>'
                . '</div>'
            . '</template>'
        . '</div>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        // $fosterParent = $template->content->querySelector('#fosterParent');
        $fosterParent = $template->content->getElementById('fosterParent');
        // $div = $template->content->querySelector('#orphanDiv');
        $div = $template->content->getElementById('orphanDiv');

        self::assertSame($fosterParent, $div->parentNode);
    }

    public function testTemplateIsNotAFosterParentElementWhenLowerInTheStack(): void
    {
        $doc = $this->newHTMLDocument();

        $doc->body->innerHTML = ''
                . '<div id="fosterParent">'
                . '<table id="tbl">'
                    . '<tr><td><template id="tmpl1">Template content</template></td></tr>'
                  // Misplaced <div>. It should be foster parented
                . '<div id="orphanDiv">Orphan div content</div>'
                    . '<tr><td>Cell 2</td></tr>'
                . '</table>'
            . '</div>'
        . '</div>';

        // $t = $doc->querySelector('#tmpl1');
        $t = $doc->getElementById('tmpl1');
        // $fosterParent = $doc->querySelector('#fosterParent');
        $fosterParent = $doc->getElementById('fosterParent');
        // $div = $doc->querySelector('#orphanDiv');
        $div = $doc->getElementById('orphanDiv');

        self::assertSame($fosterParent, $div->parentNode);
    }
}
