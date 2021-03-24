<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_body_insertion_mode;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-body-insertion-mode/start-tag-body.html
 */
class Start_tag_bodyTest extends TestCase
{
    use CommonTrait;

    public function testBodyTagOnly(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl"><body></template>';
        // $template = $doc->querySelector('#tmpl');
        $template = $doc->getElementById('tmpl');

        self::assertSame(0, $template->content->childNodes->length);
    }

    public function testBodyTagContainingSomeText(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl"><body>Body text content</body></template>';
        // $template = $doc->querySelector('#tmpl');
        $template = $doc->getElementById('tmpl');

        // self::assertNotNull($doc->querySelector('#tmpl'));
        self::assertNotNull($doc->getElementById('tmpl'));
        self::assertSame(1, $template->content->childNodes->length);
        self::assertSame(Node::TEXT_NODE, $template->content->firstChild->nodeType);
    }

    public function testBodyTagContainingSomeOtherElements(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl"><body>'
            . '<div id="div1">DIV 1</div>'
            . '<div id="div2">DIV 2</div>'
            . '</body></template>';
        // $template = $doc->querySelector('#tmpl');
        $template = $doc->getElementById('tmpl');

        // self::assertNotNull($template->content->querySelector('body'));
        self::assertNotSame('BODY', $template->content->firstElementChild->nodeName);
        self::assertSame(2, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        // self::assertNotNull($template->content->querySelector('#div2'));
        self::assertNotNull($template->content->getElementById('div1'));
        self::assertNotNull($template->content->getElementById('div2'));
    }

    public function testNestedTemplateTagContainingBodyTagWithSomeOtherElements(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl1"><template id="tmpl2"><body>'
            . '<div id="div1">DIV 1</div>'
            . '<div id="div2">DIV 2</div>'
            . '</body></template></template>';
        // $template = $doc->querySelector('#tmpl1')->content->querySelector('#tmpl2');
        $template = $doc->getElementById('tmpl1')->content->getElementById('tmpl2');

        // self::assertNotNull($template->content->querySelector('body'));
        self::assertNotSame('BODY', $template->content->firstElementChild->nodeName);
        self::assertSame(2, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        // self::assertNotNull($template->content->querySelector('#div2'));
        self::assertNotNull($template->content->getElementById('div1'));
        self::assertNotNull($template->content->getElementById('div2'));
    }
}
