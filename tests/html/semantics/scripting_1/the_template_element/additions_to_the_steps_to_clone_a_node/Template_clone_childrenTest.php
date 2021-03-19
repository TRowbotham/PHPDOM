<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\scripting_1\the_template_element\additions_to_the_steps_to_clone_a_node;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/scripting-1/the-template-element/additions-to-the-steps-to-clone-a-node/template-clone-children.html
 */
class Template_clone_childrenTest extends TestCase
{
    public function testCloneNodeWithDeepTrue(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        $copy = $template->cloneNode(true);

        self::assertNotNull($copy->content);
        self::assertSame(2, $copy->content->childNodes->length);
        // self::assertNotNull($copy->content->querySelector('#div1'));
        self::assertNotNull($copy->content->getElementById('div1'));
        // self::assertNotNull($copy->content->querySelector('#div2'));
        self::assertNotNull($copy->content->getElementById('div2'));
    }

    public function testCloneNodeWithDeepDefault(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        $copy = $template->cloneNode();

        self::assertNotNull($copy->content);
        self::assertSame(0, $copy->content->childNodes->length);
    }

    public function testCloneNodeWithDeepFalse(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('Test Document');
        $doc->body->innerHTML = '<template id="tmpl1">'
            . '<div id="div1">This is div inside template</div>'
            . '<div id="div2">This is another div inside template</div>'
            . '</template>';

        // $template = $doc->querySelector('#tmpl1');
        $template = $doc->getElementById('tmpl1');
        $copy = $template->cloneNode(false);

        self::assertNotNull($copy->content);
        self::assertSame(0, $copy->content->childNodes->length);
    }
}
