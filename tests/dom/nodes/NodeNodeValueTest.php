<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-nodeValue.html
 */
class NodeNodeValueTest extends TestCase
{
    use DocumentGetter;

    /**
     * Text.nodeValue
     */
    public function test1()
    {
        $document = $this->getHTMLDocument();
        $the_text = $document->createTextNode('A span!');
        $this->assertEquals('A span!', $the_text->nodeValue);
        $this->assertEquals('A span!', $the_text->data);
        $the_text->nodeValue = 'test again';
        $this->assertEquals('test again', $the_text->nodeValue);
        $this->assertEquals('test again', $the_text->data);
        $the_text->nodeValue = null;
        $this->assertEquals('', $the_text->nodeValue);
        $this->assertEquals('', $the_text->data);
    }

    /**
     * Comment.nodeValue
     */
    public function test2()
    {
        $document = $this->getHTMLDocument();
        $the_comment = $document->createComment('A comment!');
        $this->assertEquals('A comment!', $the_comment->nodeValue);
        $this->assertEquals('A comment!', $the_comment->data);
        $the_comment->nodeValue = 'test again';
        $this->assertEquals('test again', $the_comment->nodeValue);
        $this->assertEquals('test again', $the_comment->data);
        $the_comment->nodeValue = null;
        $this->assertEquals('', $the_comment->nodeValue);
        $this->assertEquals('', $the_comment->data);
    }

    /**
     * ProcessingInstruction.nodeValue
     */
    public function test3()
    {
        $document = $this->getHTMLDocument();
        $the_pi = $document->createProcessingInstruction('pi', 'A PI!');
        $this->assertEquals('A PI!', $the_pi->nodeValue);
        $this->assertEquals('A PI!', $the_pi->data);
        $the_pi->nodeValue = 'test again';
        $this->assertEquals('test again', $the_pi->nodeValue);
        $this->assertEquals('test again', $the_pi->data);
        $the_pi->nodeValue = null;
        $this->assertEquals('', $the_pi->nodeValue);
        $this->assertEquals('', $the_pi->data);
    }

    /**
     * Element.nodeValue
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $the_link = $document->createElement('a');
        $this->assertNull($the_link->nodeValue);
        $the_link->nodeValue = 'foo';
        $this->assertNull($the_link->nodeValue);
    }

    /**
     * Document.nodeValue
     */
    public function test5()
    {
        $document = $this->getHTMLDocument();
        $this->assertNull($document->nodeValue);
        $document->nodeValue = 'foo';
        $this->assertNull($document->nodeValue);
    }

    /**
     * DocumentFragment.nodeValue
     */
    public function test6()
    {
        $document = $this->getHTMLDocument();
        $the_frag = $document->createDocumentFragment();
        $this->assertNull($the_frag->nodeValue);
        $the_frag->nodeValue = 'foo';
        $this->assertNull($the_frag->nodeValue);
    }

    /**
     * DocumentType.nodeValue
     */
    public function test7()
    {
        $document = $this->getHTMLDocument();
        $the_doctype = $document->doctype;
        $this->assertNull($the_doctype->nodeValue);
        $the_doctype->nodeValue = 'foo';
        $this->assertNull($the_doctype->nodeValue);
    }
}
