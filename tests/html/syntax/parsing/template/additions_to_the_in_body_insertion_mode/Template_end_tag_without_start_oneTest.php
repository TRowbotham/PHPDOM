<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_body_insertion_mode;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-body-insertion-mode/template-end-tag-without-start-one.html
 */
class Template_end_tag_without_start_oneTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testTemplateEndTagInHTMLBodyWithoutStartOneSouldBeIgnored(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '</template>';

        self::assertSame(0, $doc->body->childNodes->length);
    }

    public function testValidTemplateElementAndTemplateEndTagAfterIt(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl"></template></template>';

        self::assertSame(1, $doc->body->childNodes->length);
        // self::assertNotNull($doc->querySelector('#tmpl'));
        self::assertNotNull($doc->getElementById('tmpl'));
    }

    public function testValidTemplateElementAndTemplateEndTagBeforeIt(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '</template><template id="tmpl"></template>';

        self::assertSame(1, $doc->body->childNodes->length);
        // self::assertNotNull($doc->querySelector('#tmpl'));
        self::assertNotNull($doc->getElementById('tmpl'));
    }

    public function testValidTemplateElementAndTemplateEndTagBeforeThem(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '</template><template id="tmpl"></template><title></title>';

        self::assertSame(2, $doc->body->childNodes->length);
        // self::assertNotNull($doc->querySelector('#tmpl'));
        self::assertNotNull($doc->getElementById('tmpl'));
        // self::assertNotNull($doc->querySelector('title'));
        self::assertNotNull($doc->getElementsByTagName('title')[0]);
    }

    public function testValidTemplateElementAndTemplateEndTagAfterThem(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl"></template><title></title></template>';

        self::assertSame(2, $doc->body->childNodes->length);
        // self::assertNotNull($doc->querySelector('#tmpl'));
        self::assertNotNull($doc->getElementById('tmpl'));
        // self::assertNotNull($doc->querySelector('title'));
        self::assertNotNull($doc->getElementsByTagName('title')[0]);
    }

    public function testHtmlDocumentLoadedFromFile(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'end-template-tag-in-body.html')->contentDocument;

        // self::assertNull($doc->body->querySelector('template'));
        // self::assertNotNull($doc->body->querySelector('div'));
        self::assertNull($doc->getElementsByTagName('template')[0]);
        self::assertNotNull($doc->getElementsByTagName('div')[0]);
    }
}
