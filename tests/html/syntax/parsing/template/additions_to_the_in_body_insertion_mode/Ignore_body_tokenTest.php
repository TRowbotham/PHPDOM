<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_body_insertion_mode;

use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-body-insertion-mode/ignore-body-token.html
 */
class Ignore_body_tokenTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testEmptyBodyElementAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<body></body>';
        $doc->body->appendChild($template);

        self::assertSame(0, $template->content->childNodes->length);
    }

    public function testNotEmptyBodyElementAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<body><div>Some content</div></body>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        self::assertSame('DIV', $template->content->firstChild->nodeName);
    }

    public function testBodyElementAndSomeValidElementAfterBodyAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<body><div <div id="div1">Some content</div></body><div id="div2">Some valid content</div>';
        $doc->body->appendChild($template);

        self::assertSame(2, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        // self::assertNotNull($template->content->querySelector('#div2'));
        self::assertNotNull($template->content->getElementById('div1'));
        self::assertNotNull($template->content->getElementById('div2'));
    }

    public function testBodyElementAndSomeValidElementBeforeBodyAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<div id="div1">Some valid content</div><body><div id="div2">Some content</div></body>';
        $doc->body->appendChild($template);

        self::assertSame(2, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        // self::assertNotNull($template->content->querySelector('#div2'));
        self::assertNotNull($template->content->getElementById('div1'));
        self::assertNotNull($template->content->getElementById('div2'));
    }

    public function testTemplateWithNotEmptyBodyElementInsideAssignedToAnotherTemplatesInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<template id="t2"><body><span>Body!<span></body></template>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#t2'));
        self::assertNotNull($template->content->getElementById('t2'));

        // $nestedTemplate = $template->content->querySelector('#t2');
        $nestedTemplate = $template->content->getElementById('t2');

        self::assertSame(1, $nestedTemplate->content->childNodes->length);
        self::assertSame('SPAN', $nestedTemplate->content->firstChild->nodeName);
    }

    public function testLoadingAHTMLFileWithBodyTagInsideTemplate(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents-body.html')->contentDocument;
        // $template = $doc->body->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertSame(0, $template->content->childNodes->length);
    }
}
