<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\additions_to_the_in_body_insertion_mode;

use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/additions-to-the-in-body-insertion-mode/start-tag-html.html
 */
class Start_tag_htmlTest extends TestCase
{
    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    // test <template><html class="htmlClass"></html></template><html id="htmlId" tabindex="5">
    // id attribute should be added to root <html> element
    // tabindex attribute should not be modified
    //class attribute should be ignored
    public function testHtmlStartTagShouldAddOnlyAbsentAttributes(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'html-start-tag.html')->contentDocument;

        // $template = $doc->body->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];
        $html = $doc->documentElement;

        self::assertSame('5', $html->getAttribute('tabindex'));
        self::assertSame('htmlId', $html->getAttribute('id'));
        self::assertFalse($html->hasAttribute('class'));
        self::assertSame(0, $template->content->childNodes->length);
    }
}
