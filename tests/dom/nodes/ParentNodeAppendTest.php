<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/ParentNode-append.html
 */
class ParentNodeAppendTest extends NodeTestCase
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
                [$document->createDocumentFragment()]
            ];
        }

        return $this->tests;
    }

    /**
     * .append() without any argument, on a parent having no child.
     *
     * @dataProvider getTests
     */
    public function test1($node)
    {
        $parent = $node->cloneNode();
        $parent->append();
        $this->assertSame([], iterator_to_array($parent->childNodes));
    }

    /**
     * .append() with null as an argument, on a parent having no child.
     *
     * @dataProvider getTests
     */
    public function test2($node)
    {
        $parent = $node->cloneNode();
        $parent->append(null);
        $this->assertEquals('null', $parent->childNodes[0]->textContent);
    }

    /**
     * .append() with only text as an argument, on a parent having no child.
     *
     * @dataProvider getTests
     */
    public function test3($node)
    {
        $parent = $node->cloneNode();
        $parent->append('text');
        $this->assertEquals('text', $parent->childNodes[0]->textContent);
    }

    /**
     * .append() with only one element as an argument, on a parent having no
     * child.
     *
     * @dataProvider getTests
     */
    public function test4($node)
    {
        $parent = $node->cloneNode();
        $x = $this->getHTMLDocument()->createElement('x');
        $parent->append($x);
        $this->assertSame([$x], iterator_to_array($parent->childNodes));
    }

    /**
     * .append() with null as an argument, on a parent having a child.
     *
     * @dataProvider getTests
     */
    public function test5($node)
    {
        $parent = $node->cloneNode();
        $child = $this->getHTMLDocument()->createElement('test');
        $parent->append($child);
        $parent->append(null);
        $this->assertSame($child, $parent->childNodes[0]);
        $this->assertEquals('null', $parent->childNodes[1]->textContent);
    }

    /**
     * .append() with one element and text as argument, on a parent having a
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
        $parent->append($child);
        $parent->append($x, 'text');
        $this->assertSame($child, $parent->childNodes[0]);
        $this->assertSame($x, $parent->childNodes[1]);
        $this->assertEquals('text', $parent->childNodes[2]->textContent);
    }

    public static function getDocumentName(): string
    {
        return 'ParentNode-append.html';
    }

    public function getMethodName(): string
    {
        return 'append';
    }
}
