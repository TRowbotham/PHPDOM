<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/rootNode.html
 */
class NodeGetRootNodeTest extends TestCase
{
    use DocumentGetter;

    /**
     * getRootNode() must return the context object when it does not have any
     * parent.
     */
    public function test1()
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElement('div');
        $this->assertSame($element, $element->getRootNode());

        $text = $document->createTextNode('');
        $this->assertSame($text, $text->getRootNode());

        $processingInstruction = $document->createProcessingInstruction(
            'target',
            'data'
        );
        $this->assertSame(
            $processingInstruction,
            $processingInstruction->getRootNode()
        );
    }

    /**
     * getRootNode() must return the parent node of the context object when the
     * context object has a single ancestor not in a document.
     */
    public function test2()
    {
        $document = $this->getHTMLDocument();
        $parent = $document->createElement('div');

        $element = $document->createElement('div');
        $parent->appendChild($element);
        $this->assertSame($parent, $element->getRootNode());

        $text = $document->createTextNode('');
        $parent->appendChild($text);
        $this->assertSame($parent, $text->getRootNode());

        $processingInstruction = $document->createProcessingInstruction(
            'target',
            'data'
        );
        $parent->appendChild($processingInstruction);
        $this->assertSame(
            $parent,
            $processingInstruction->getRootNode()
        );
    }

    /**
     * getRootNode() must return the document when a node is in document.
     */
    public function test3()
    {
        $document = $this->getHTMLDocument();
        $parent = $document->createElement('div');
        $document->body->appendChild($parent);

        $element = $document->createElement('div');
        $parent->appendChild($element);
        $this->assertSame($document, $element->getRootNode());

        $text = $document->createTextNode('');
        $parent->appendChild($text);
        $this->assertSame($document, $text->getRootNode());

        $processingInstruction = $document->createProcessingInstruction(
            'target',
            'data'
        );
        $parent->appendChild($processingInstruction);
        $this->assertSame(
            $document,
            $processingInstruction->getRootNode()
        );
    }

    /**
     * getRootNode() must return a document fragment when a node in in the
     * fragment.
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $fragment = $document->createDocumentFragment();
        $parent = $document->createElement('div');
        $fragment->appendChild($parent);

        $element = $document->createElement('div');
        $parent->appendChild($element);
        $this->assertSame($fragment, $element->getRootNode());

        $text = $document->createTextNode('');
        $parent->appendChild($text);
        $this->assertSame($fragment, $text->getRootNode());

        $processingInstruction = $document->createProcessingInstruction(
            'target',
            'data'
        );
        $parent->appendChild($processingInstruction);
        $this->assertSame(
            $fragment,
            $processingInstruction->getRootNode()
        );
    }
}
