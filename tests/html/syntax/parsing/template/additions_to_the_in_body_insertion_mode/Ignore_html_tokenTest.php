<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_body_insertion_mode;

use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-body-insertion-mode/ignore-html-token.html
 */
class Ignore_html_tokenTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testHtmlElementAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<html><body></body></html>';
        $doc->body->appendChild($template);

        self::assertSame(0, $template->content->childNodes->length);
    }

    public function testHtmlElementAndSomeValidElementBeforeItAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<div id="div1">Some text</div><html><body></body></html>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
    }

    public function testHtmlElementAndSomeValidElementAfterItAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<html><body></body></html><div id="div1">Some text</div>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
    }

    public function testHtmlTagInsideTemplateTagAssignedToAnotherTemplatesInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<template id="t2"><html><body></body></html></template>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#t2'));
        self::assertNotNull($template->content->getElementById('t2'));

        // $nestedTemplate = $template->content->querySelector('#t2');
        $nestedTemplate = $template->content->getElementById('t2');

        self::assertSame(0, $nestedTemplate->content->childNodes->length);
    }

    public function testSomeValidElementInsideHtmlElement(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<html><div id="div1">Some text</div></html>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
    }

    public function testValidElementInsideAndBetweenHtmlAndBodyElements(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<html><span id="span1">Span</span><body><div id="div1">Some text</div><body></html>';
        $doc->body->appendChild($template);

        self::assertSame(2, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
        // self::assertNotNull($template->content->querySelector('#span1'));
        self::assertNotNull($template->content->getElementById('span1'));
    }

    public function testLoadingAHTMLFileWithHtmlTagInsideTemplate(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents-html.html')->contentDocument;
        // $template = $doc->body->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertSame(0, $template->content->childNodes->length);
    }
}
