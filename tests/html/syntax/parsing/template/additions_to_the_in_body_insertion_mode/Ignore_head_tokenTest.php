<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_body_insertion_mode;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-body-insertion-mode/ignore-head-token.html
 */
class Ignore_head_tokenTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testEmptyHeadElementAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<head></head>';
        $doc->body->appendChild($template);

        self::assertSame(0, $template->content->childNodes->length);
    }

    public function testNotEmptyHeadElementAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<head><title>test</title></head>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        self::assertSame('TITLE', $template->content->firstChild->nodeName);
    }

    public function testHeadElementAndSomeValidElementBeforeItAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<div id="div1">Some text</div><head><title>test</title></head>';
        $doc->body->appendChild($template);

        self::assertSame(2, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
        self::assertNotNull('TITLE', $template->content->lastChild->tagName);
    }

    public function testHeadElementAndSomeValidElementAfterItAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<head><title>test</title></head><div id="div1">Some text</div>';
        $doc->body->appendChild($template);

        self::assertSame(2, $template->content->childNodes->length);
        self::assertNotNull('TITLE', $template->content->firstChild->tagName);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
    }

    public function testHeadTagInsideTemplateAssignedToAnotherTemplatesInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<template id="t2"><head><title>test</title></head></template>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#t2'));
        self::assertNotNull($template->content->getElementById('t2'));

        // $nestedTemplate = $template->content->querySelector('#t2');
        $nestedTemplate = $template->content->getElementById('t2');

        self::assertSame(1, $nestedTemplate->content->childNodes->length);
        self::assertSame('TITLE', $nestedTemplate->content->firstChild->tagName);
    }

    public function testLoadingAHTMLFileWithHeadTagInsideTemplate(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents-head.html')->contentDocument;
        // $template = $doc->body->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertSame(0, $template->content->childNodes->length);
    }
}
