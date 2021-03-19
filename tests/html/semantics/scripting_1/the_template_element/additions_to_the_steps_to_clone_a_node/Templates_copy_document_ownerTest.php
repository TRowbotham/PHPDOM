<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\scripting_1\the_template_element\additions_to_the_steps_to_clone_a_node;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\ranges\FakeIframe;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/scripting-1/the-template-element/additions-to-the-steps-to-clone-a-node/templates-copy-document-owner.html
 */
class Templates_copy_document_ownerTest extends TestCase
{
    public function testOwnerDocumentOfClonedTemplateContentIsSetToTemplateContentOwnerWithChildren(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        $copy = $template->cloneNode(true);

        self::assertSame(2, $copy->content->childNodes->length);
        $this->checkOwnerDocument($copy->content, $template->content->ownerDocument);
    }

    public function testOwnerDocumentOfClonedTemplateContentIsSetToTemplateContentOwnerWithoutChildren(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        $copy = $template->cloneNode();

        self::assertSame(0, $copy->content->childNodes->length);
        $this->checkOwnerDocument($copy->content, $template->content->ownerDocument);
    }

    public function testOwnerDocumentOfClonedTemplateContentIsSetToTemplateContentOwnerCloneNodeNoArgs(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        $copy = $template->cloneNode();

        self::assertSame(0, $copy->content->childNodes->length);
        $this->checkOwnerDocument($copy->content, $template->content->ownerDocument);
    }

    public function testOwnerDocumentOfClonedTemplateContentIsSetToTemplateContentOwnerNestedTemplate(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
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
        $copy = $template->cloneNode(true);

        self::assertSame(3, $copy->content->childNodes->length);
        $this->checkOwnerDocument($copy->content, $template->content->ownerDocument);
    }

    public function testOwnerDocumentOfClonedTemplateContentIsSetToTemplateContentOwnerInIframe(): void
    {
        // $doc = $context->iframes[0]->contentDocument;
        $doc = FakeIframe::load(__DIR__ . DS . '..' . DS . 'resources' . DS . 'template-contents.html')->contentDocument;

        // $template = $doc->body->querySelector('template');
        $template = $doc->getElementsByTagName('template')[0];
        $copy = $template->cloneNode(true);

        $this->checkOwnerDocument($copy->content, $template->content->ownerDocument);
    }

    private function checkOwnerDocument($node, $doc): void
    {
        if ($node !== null) {
            self::assertSame($doc, $node->ownerDocument);

            for ($i = 0; $i < $node->childNodes->length; ++$i) {
                if ($node->childNodes[$i]->nodeType === Node::ELEMENT_NODE) {
                    $this->checkOwnerDocument($node->childNodes[$i], $doc);

                    if ($node->childNodes[$i]->nodeName === 'TEMPLATE') {
                        $this->checkOwnerDocument($node->childNodes[$i]->content, $doc);
                    }
                }
            }
        }
    }
}
