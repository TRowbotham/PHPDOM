<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-parentElement.html
 */
class NodeParentElementTest extends TestCase
{
    use DocumentGetter;

    /**
     * When the parent is null, parentElement should be null
     */
    public function test1()
    {
        $this->assertNull($this->getHTMLDocument()->parentElement);
    }

    /**
     * When the parent is a document, parentElement should be null (doctype)
     */
    public function test2()
    {
        $this->assertNull($this->getHTMLDocument()->doctype->parentElement);
    }

    /**
     * When the parent is a document, parentElement should be null (element)
     */
    public function test3()
    {
        $this->assertNull(
            $this->getHTMLDocument()->documentElement->parentElement
        );
    }

    /**
     * When the parent is a document, parentElement should be null (comment)
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $comment = $document->appendChild($document->createComment('foo'));
        $this->assertNull($comment->parentElement);
    }

    /**
     * parentElement should return null for children of DocumentFragments
     * (element)
     */
    public function test5()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $this->assertNull($df->parentElement);
        $el = $document->createElement('div');
        $this->assertNull($el->parentElement);
        $df->appendChild($el);
        $this->assertSame($df, $el->parentNode);
        $this->assertNull($el->parentElement);
    }

    /**
     * parentElement should return null for children of DocumentFragments (text)
     */
    public function test6()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $this->assertNull($df->parentElement);
        $text = $document->createTextNode('bar');
        $this->assertNull($text->parentElement);
        $df->appendChild($text);
        $this->assertSame($df, $text->parentNode);
        $this->assertNull($text->parentElement);
    }

    /**
     * parentElement should work correctly with DocumentFragments (element)
     */
    public function test7()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $parent = $document->createElement('div');
        $df->appendChild($parent);
        $this->assertNull($df->parentElement);
        $el = $document->createElement('div');
        $this->assertNull($el->parentElement);
        $parent->appendChild($el);
        $this->assertSame($parent, $el->parentElement);
    }

    /**
     * parentElement should work correctly with DocumentFragments (text)
     */
    public function test8()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $parent = $document->createElement("div");
        $df->appendChild($parent);
        $text = $document->createTextNode("bar");
        $this->assertNull($text->parentElement);
        $parent->appendChild($text);
        $this->assertSame($parent, $text->parentElement);
    }

    /**
     * parentElement should work correctly in disconnected subtrees (element)
     */
    public function test9()
    {
        $document = $this->getHTMLDocument();
        $parent = $document->createElement('div');
        $el = $document->createElement('div');
        $this->assertNull($el->parentElement);
        $parent->appendChild($el);
        $this->assertSame($parent, $el->parentElement);
    }

    /**
     * parentElement should work correctly in disconnected subtrees (text)
     */
    public function test10()
    {
        $document = $this->getHTMLDocument();
        $parent = $document->createElement('div');
        $text = $document->createTextNode('bar');
        $this->assertNull($text->parentElement);
        $parent->appendChild($text);
        $this->assertSame($parent, $text->parentElement);
    }

    /**
     * parentElement should work correctly in a document (element)
     */
    public function test11()
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $this->assertNull($el->parentElement);
        $document->body->appendChild($el);
        $this->assertSame($document->body, $el->parentElement);
    }

    /**
     * parentElement should work correctly in a document (text)
     */
    public function test12()
    {
        $document = $this->getHTMLDocument();
        $text = $document->createElement('div');
        $this->assertNull($text->parentElement);
        $document->body->appendChild($text);
        $this->assertEquals($document->body, $text->parentElement);
    }
}
