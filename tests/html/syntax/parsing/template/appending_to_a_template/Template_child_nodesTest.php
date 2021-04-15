<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\appending_to_a_template;

use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/appending-to-a-template/template-child-nodes.html
 */
class Template_child_nodesTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testTemplateChildNodesMustBeAppendedToTemplateContentNode(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');

        self::assertSame(0, $template->childNodes->length);
        self::assertSame(2, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        // self::assertNotNull($template->content->querySelector('#div2'));
        self::assertNotNull($template->content->getElementById('div1'));
        self::assertNotNull($template->content->getElementById('div2'));
    }

    public function testNestedTemplate(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '<template id="tmpl2">'
            . '<div id="div3">This is div inside nested template</div>'
            . '<div id="div4">This is another div inside nested template</div>'
            . '</template>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');

        self::assertSame(0, $template->childNodes->length);
        self::assertSame(3, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        // self::assertNotNull($template->content->querySelector('#div2'));
        self::assertNotNull($template->content->getElementById('div1'));
        self::assertNotNull($template->content->getElementById('div2'));

        // $nestedTemplate = $template->content->querySelector('#tmpl2');
        $nestedTemplate = $template->content->getElementById('tmpl2');

        self::assertSame(0, $nestedTemplate->childNodes->length);
        self::assertSame(2, $nestedTemplate->content->childNodes->length);

        // self::assertNotNull($nestedTemplate->content->querySelector('#div3'));
        // self::assertNotNull($nestedTemplate->content->querySelector('#div4'));
        self::assertNotNull($nestedTemplate->content->getElementById('div3'));
        self::assertNotNull($nestedTemplate->content->getElementById('div4'));
    }

    public function testLoadHTMLDocumentFromAFile(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents.html')->contentDocument;

        // $template = $doc->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertSame(0, $template->childNodes->length);
        // self::assertNotNull($template->content->querySelector('div'));
        self::assertSame('DIV', $template->content->firstElementChild->nodeName);
    }

    public function testLoadHTMLDocumentFromAFileWithNestedTemplate(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents-nested.html')->contentDocument;

        // $template = $doc->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertSame(0, $template->childNodes->length);

        // $nestedTemplate = $template->content->querySelector('template');
        $nestedTemplate = $template->content->firstElementChild;

        self::assertNotNull($nestedTemplate);
        self::assertSame(0, $nestedTemplate->childNodes->length);
        // self::assertNotNull($nestedTemplate->content->querySelector('div'));
        self::assertSame('DIV', $nestedTemplate->content->firstElementChild->nodeName);
    }
}
