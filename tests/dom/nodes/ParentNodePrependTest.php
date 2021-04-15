<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;

use function iterator_to_array;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/ParentNode-prepend.html
 */
class ParentNodePrependTest extends NodeTestCase
{
    use DocumentGetter;
    use PreinsertionValidationHierarchyTrait;

    protected $tests;

    public function getTests()
    {
        if (!$this->tests) {
            $document = $this->getHTMLDocument();
            $this->tests = [
                [$document->createElement('div')],
                [$document->createDocumentFragment()],
            ];
        }

        return $this->tests;
    }

    /**
     * .prepend() without any argument, on a parent having no child.
     *
     * @dataProvider getTests
     */
    public function test1($node)
    {
        $parent = $node->cloneNode();
        $parent->prepend();
        $this->assertSame([], iterator_to_array($parent->childNodes));
    }

    /**
     * .prepend() with null as an argument, on a parent having no child.
     *
     * @dataProvider getTests
     */
    public function test2($node)
    {
        $parent = $node->cloneNode();
        $parent->prepend(null);
        $this->assertEquals('null', $parent->childNodes[0]->textContent);
    }

    /**
     * .prepend() with only text as an argument, on a parent having no child.
     *
     * @dataProvider getTests
     */
    public function test3($node)
    {
        $parent = $node->cloneNode();
        $parent->prepend('text');
        $this->assertEquals('text', $parent->childNodes[0]->textContent);
    }

    /**
     * .prepend() with only one element as an argument, on a parent having no
     * child.
     *
     * @dataProvider getTests
     */
    public function test4($node)
    {
        $parent = $node->cloneNode();
        $x = $this->getHTMLDocument()->createElement('x');
        $parent->prepend($x);
        $this->assertSame([$x], iterator_to_array($parent->childNodes));
    }

    /**
     * .prepend() with null as an argument, on a parent having a child.
     *
     * @dataProvider getTests
     */
    public function test5($node)
    {
        $parent = $node->cloneNode();
        $child = $this->getHTMLDocument()->createElement('test');
        $parent->appendChild($child);
        $parent->prepend(null);
        $this->assertEquals('null', $parent->childNodes[0]->textContent);
        $this->assertSame($child, $parent->childNodes[1]);
    }

    /**
     * .prepend() with one element and text as argument, on a parent having a
     * child.
     *
     * @dataProvider getTests
     */
    public function test6($node)
    {
        $parent = $node->cloneNode();
        $document = $this->getHTMLDocument();
        $x = $document->createElement('x');
        $child = $document->createElement('test');
        $parent->appendChild($child);
        $parent->prepend($x, 'text');
        $this->assertSame($x, $parent->childNodes[0]);
        $this->assertEquals('text', $parent->childNodes[1]->textContent);
        $this->assertSame($child, $parent->childNodes[2]);
    }

    public static function getDocumentName(): string
    {
        return 'ParentNode-prepend.html';
    }

    public function getMethodName(): string
    {
        return 'prepend';
    }
}
