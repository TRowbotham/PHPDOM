<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing\template\creating_an_element_for_the_token;

use Generator;
use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Element\HTML\HTMLTemplateElement;
use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\html\resources\CommonTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing/template/creating-an-element-for-the-token/template-owner-document.html
 */
class Template_owner_documentTest extends TestCase
{
    use CommonTrait;

    private const RESOURCES_DIR = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'semantics' . DS . 'scripting_1' . DS . 'the_template_element' . DS . 'resources';

    public function testTemplateElementInsideTheDiv(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<div><template id="tmpl1"><div id="div">DIV</div></template></div>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');

        // $div = $template->content->querySelector('#div');
        $div = $template->content->getElementById('div');

        self::assertSame($template->content->ownerDocument, $div->ownerDocument);
    }

    public function testTemplateElementInTheRootOfTheBody(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl1"><div id="div">DIV</div></template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');

        // $div = $template->content->querySelector('#div');
        $div = $template->content->getElementById('div');

        self::assertSame($template->content->ownerDocument, $div->ownerDocument);
    }

    public function testOwnerDocumentPropertyOfTheElementInANestedTemplate(): void
    {
        $doc = $this->newHTMLDocument();
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<template id="tmpl2"><div id="div">DIV</div></template></template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');

        // $nestedTemplate = $template->content->querySelector('#tmpl2');
        $nestedTemplate = $template->content->getElementById('tmpl2');

        // $div = $nestedTemplate->content->querySelector('#div');
        $div = $nestedTemplate->content->getElementById('div');

        self::assertSame($nestedTemplate->content->ownerDocument, $div->ownerDocument);
    }

    public function testOwnerDocumentPropertyOfTheElementInATemplateLoadingDocumentFromFile(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents.html')->contentDocument;

        // $template = $doc->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        // $div = $template->content->querySelector('div');
        $div = $template->content->firstElementChild;

        self::assertSame($template->content->ownerDocument, $div->ownerDocument);
    }

    public function testOwnerDocumentPropertyOfTheElementInANestedTemplateLoadingDocumentFromFile(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'template-contents-nested.html')->contentDocument;

        // $template = $doc->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];

        // $nestedTemplate = $template->content->querySelector('template');
        $nestedTemplate = $template->content->firstElementChild;

        self::assertSame($template->content->ownerDocument, $nestedTemplate->ownerDocument);

        // $div = $nestedTemplate->content->querySelector('div');
        $div = $nestedTemplate->content->firstElementChild;

        self::assertSame($nestedTemplate->content->ownerDocument, $div->ownerDocument);
    }

    public function testOwnerDocumentPropertyOfTwoElementsInANestedTemplateLoadingDocumentFromFile(): void
    {
        $doc = FakeIframe::load(self::RESOURCES_DIR . DS . 'two-templates.html')->contentDocument;

        // $template1 = $doc->querySelector('template1');
        $template1 = $doc->getElementById('template1');
        // $div1 = $template1->content->querySelector('div');
        $div1 = $template1->content->firstElementChild;
        // $template2 = $doc->querySelector('template2');
        $template2 = $doc->getElementById('template2');
        // $div2 = $template2->content->querySelector('div');
        $div2 = $template2->content->firstElementChild;

        self::assertSame($template1->content->ownerDocument, $div1->ownerDocument);
        self::assertSame($template2->content->ownerDocument, $div2->ownerDocument);
        self::assertSame($div1->ownerDocument, $div2->ownerDocument);
    }

    /**
     * @dataProvider compareOwnersDataProvider
     */
    public function testCompareOwners(Element $element, HTMLTemplateElement $template): void
    {
        self::assertSame($template->content->ownerDocument, $element->ownerDocument);
    }

    public function compareOwnersDataProvider(): Generator
    {
        foreach (self::$HTML5_ELEMENTS as $value) {
            if ($value !== 'body' && $value !== 'html' && $value !== 'head' && $value !== 'frameset') {
                $doc = $this->newHTMLDocument();

                if ($this->isVoidElement($value)) {
                    $doc->body->innerHTML = '<template><' . $value . '/></template>';
                } else {
                    $doc->body->innerHTML = '<template><' . $value . '></' . $value . '></template>';
                }

                // $template = $doc->querySelector('template');
                $template = $doc->getElementsByTagName('template')[0];
                // $element = $template->content->querySelector($value);
                $element = $template->content->firstElementChild;

                yield [$element, $template];
            }
        }
    }
}
