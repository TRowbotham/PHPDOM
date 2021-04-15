<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\Text;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-textContent.html
 */
class NodeTextContentTest extends TestCase
{
    use DocumentGetter;

    /**
     * For an empty Element, textContent should be the empty string.
     */
    public function test1()
    {
        $document = $this->getHTMLDocument();
        $element = $document->createElement('div');
        $this->assertEquals('', $element->textContent);
    }

    /**
     * For an empty DocumentFragment, textContent should be the empty string.
     */
    public function test2()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals(
            '',
            $document->createDocumentFragment()->textContent
        );
    }

    /**
     * Element with children.
     */
    public function test3()
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createComment(' abc '));
        $el->appendChild($document->createTextNode("\tDEF\t"));
        $el->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $this->assertEquals("\tDEF\t", $el->textContent);
    }

    /**
     * Element with descendants.
     */
    public function test4()
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $child = $document->createElement('div');
        $el->appendChild($child);
        $child->appendChild($document->createComment(' abc '));
        $child->appendChild($document->createTextNode("\tDEF\t"));
        $child->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $this->assertEquals("\tDEF\t", $el->textContent);
    }

    /**
     * DocumentFragment with children.
     */
    public function test5()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $df->appendChild($document->createComment(' abc '));
        $df->appendChild($document->createTextNode("\tDEF\t"));
        $df->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $this->assertEquals("\tDEF\t", $df->textContent);
    }

    /**
     * DocumentFragment with descendants.
     */
    public function test6()
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $child = $document->createElement('div');
        $df->appendChild($child);
        $child->appendChild($document->createComment(' abc '));
        $child->appendChild($document->createTextNode("\tDEF\t"));
        $child->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $this->assertEquals("\tDEF\t", $df->textContent);
    }

    /**
     * For an empty Text, textContent should be the empty string.
     */
    public function test7()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals('', $document->createTextNode('')->textContent);
    }

    /**
     * For an empty ProcessingInstruction, textContent should be the empty
     * string.
     */
    public function test8()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals(
            '',
            $document->createProcessingInstruction('x', '')->textContent
        );
    }

    /**
     * For an empty Comment, textContent should be the empty string.
     */
    public function test9()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals('', $document->createComment('')->textContent);
    }

    /**
     * For a Text with data, textContent should be that data.
     */
    public function test10()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals(
            'abc',
            $document->createTextNode('abc')->textContent
        );
    }

    /**
     * For a ProcessingInstruction with data, textContent should be that data.
     */
    public function test11()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals(
            'abc',
            $document->createProcessingInstruction('x', 'abc')->textContent
        );
    }

    /**
     * For a Comment with data, textContent should be that data.
     */
    public function test12()
    {
        $document = $this->getHTMLDocument();
        $this->assertEquals(
            'abc',
            $document->createComment('abc')->textContent
        );
    }

    public function documentsProvider()
    {
        $document = $this->getHTMLDocument();

        return [
            [$document],
            [$document->implementation->createDocument('', 'text', null)],
            [$document->implementation->createHTMLDocument('title')],
        ];
    }

    public function doctypesProvider()
    {
        $document = $this->getHTMLDocument();

        return [
            [$document->doctype],
            [$document->implementation->createDocumentType('x', '', '')],
        ];
    }

    /**
     * @dataProvider documentsProvider
     */
    public function test13($doc)
    {
        $this->assertNull($doc->textContent);
    }

    /**
     * @dataProvider doctypesProvider
     */
    public function test14($doctype)
    {
        $this->assertNull($doctype->textContent);
    }

    public function argumentsProvider()
    {
        return [
            [null, null],
            ['', null],
            [42, '42'],
            ['abc', 'abc'],
            ['<b>xyz<\/b>', '<b>xyz<\/b>'],
            ["d\0e", "d\0e"],
        ];
    }

    public function check($elementOrDocumentFragment, $expectation)
    {
        if ($expectation === null) {
            $this->assertEquals('', $elementOrDocumentFragment->textContent);
            $this->assertNull($elementOrDocumentFragment->firstChild);

            return;
        }

        $this->assertEquals(
            $expectation,
            $elementOrDocumentFragment->textContent
        );
        $this->assertEquals(
            1,
            $elementOrDocumentFragment->childNodes->length
        );
        $firstChild = $elementOrDocumentFragment->firstChild;
        $this->assertInstanceOf(Text::class, $firstChild);
        $this->assertEquals($expectation, $firstChild->data);
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function test15($argument, $expectation)
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $el->textContent = $argument;
        $this->check($el, $expectation);
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function test16($argument, $expectation)
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $text = $el->appendChild($document->createTextNode(''));
        $el->textContent = $argument;
        $this->check($el, $expectation);
        $this->assertNull($text->parentNode);
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function test17($argument, $expectation)
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createComment(' abc '));
        $el->appendChild($document->createTextNode("\tDEF\t"));
        $el->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $el->textContent = $argument;
        $this->check($el, $expectation);
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function test18($argument, $expectation)
    {
        $document = $this->getHTMLDocument();
        $el = $document->createElement('div');
        $child = $document->createElement('div');
        $el->appendChild($child);
        $child->appendChild($document->createComment(' abc '));
        $child->appendChild($document->createTextNode("\tDEF\t"));
        $child->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $el->textContent = $argument;
        $this->check($el, $expectation);
        $this->assertEquals(3, $child->childNodes->length);
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function test19($argument, $expectation)
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $df->textContent = $argument;
        $this->check($df, $expectation);
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function test20($argument, $expectation)
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $df->appendChild($document->createComment(' abc '));
        $df->appendChild($document->createTextNode("\tDEF\t"));
        $df->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $df->textContent = $argument;
        $this->check($df, $expectation);
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function test21($argument, $expectation)
    {
        $document = $this->getHTMLDocument();
        $df = $document->createDocumentFragment();
        $child = $document->createElement('div');
        $df->appendChild($child);
        $child->appendChild($document->createComment(' abc '));
        $child->appendChild($document->createTextNode("\tDEF\t"));
        $child->appendChild($document->createProcessingInstruction('x', ' ghi '));
        $df->textContent = $argument;
        $this->check($df, $expectation);
        $this->assertEquals(3, $child->childNodes->length);
    }

    /**
     * For a Text, textContent should set the data.
     */
    public function test22()
    {
        $document = $this->getHTMLDocument();
        $text = $document->createTextNode('abc');
        $text->textContent = 'def';
        $this->assertEquals('def', $text->textContent);
        $this->assertEquals('def', $text->data);
    }

    /**
     * For a ProcessingInstruction, textContent should set the data.
     */
    public function test23()
    {
        $document = $this->getHTMLDocument();
        $pi = $document->createProcessingInstruction('x', 'abc');
        $pi->textContent = 'def';
        $this->assertEquals('def', $pi->textContent);
        $this->assertEquals('def', $pi->data);
        $this->assertEquals('x', $pi->target);
    }

    /**
     * For a Comment, textContent should set the data.
     */
    public function test24()
    {
        $document = $this->getHTMLDocument();
        $comment = $document->createComment('abc');
        $comment->textContent = 'def';
        $this->assertEquals('def', $comment->textContent);
        $this->assertEquals('def', $comment->data);
    }

    /**
     * @dataProvider documentsProvider
     */
    public function test25($doc)
    {
        $root = $doc->documentElement;
        $doc->textContent = 'a';
        $this->assertNull($doc->textContent);
        $this->assertSame($root, $doc->documentElement);
    }

    /**
     * @dataProvider doctypesProvider
     */
    public function test26($doctype)
    {
        $props = [
            'name' => $doctype->name,
            'publicId' => $doctype->publicId,
            'systemId' => $doctype->systemId,
        ];
        $doctype->textContent = 'b';
        $this->assertNull($doctype->textContent);
        $this->assertEquals($props['name'], $doctype->name);
        $this->assertEquals($props['publicId'], $doctype->publicId);
        $this->assertEquals($props['systemId'], $doctype->systemId);
    }
}
