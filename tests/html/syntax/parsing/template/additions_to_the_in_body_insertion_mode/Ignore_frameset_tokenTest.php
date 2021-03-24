<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_body_insertion_mode;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-body-insertion-mode/ignore-frameset-token.html
 */
class Ignore_frameset_tokenTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testFramesetElementAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<frameset cols="25%,*,25%">'
            . '<frame src="frame_a.htm">'
            . '<frame src="frame_b.htm">' . '<frame src="frame_c.htm">'
            . '</frameset>';
        $doc->body->appendChild($template);

        self::assertSame(0, $template->content->childNodes->length);
    }

    public function testFramesetElementAndSomeValidElementBeforeItAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<div id="div1">Some text</div>'
            . '<frameset cols="25%,*,25%">'
            . '<frame src="frame_a.htm">'
            . '<frame src="frame_b.htm">'
            . '<frame src="frame_c.htm">'
            . '</frameset>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
    }

    public function testFramesetElementAndSomeValidElementAfteritAssignedToTemplateInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<frameset cols="25%,*,25%">'
            . '<frame src="frame_a.htm">'
            . '<frame src="frame_b.htm">'
            . '<frame src="frame_c.htm">'
            . '</frameset><div id="div1">Some text</div>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#div1'));
        self::assertNotNull($template->content->getElementById('div1'));
    }

    public function testFramesetElementInsideTemplateTagAssignedToAnotherTemplatesInnerHTML(): void
    {
        $doc = $this->newHTMLDocument();
        $template = $doc->createElement('template');
        $template->innerHTML = '<template id="t2">'
            . '<frameset cols="25%,*,25%">'
            . '<frame src="frame_a.htm">'
            . '<frame src="frame_b.htm">'
            . '<frame src="frame_c.htm">'
            . '</frameset></template>';
        $doc->body->appendChild($template);

        self::assertSame(1, $template->content->childNodes->length);
        // self::assertNotNull($template->content->querySelector('#t2'));
        self::assertNotNull($template->content->getElementById('t2'));

        // $nestedTemplate = $template->content->querySelector('#t2');
        $nestedTemplate = $template->content->getElementById('t2');

        self::assertSame(0, $nestedTemplate->content->childNodes->length);
    }

    public function testLoadingAHTMLFileWithFramsetTagInsideTemplate(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents-frameset.html')->contentDocument;
        // $template = $doc->body->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        self::assertSame(0, $template->content->childNodes->length);
    }
}
