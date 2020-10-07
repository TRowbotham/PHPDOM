<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/ParentNode-replaceChildren.html
 */
class ParentNodeReplaceChildrenTest extends TestCase
{
    use PreinsertionValidationHierarchyTrait;

    private static $testNodes = [];

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceChildrenWithoutArgsOnParentHavingNoChild(Node $node, string $nodeName): void
    {
        $parent = $node->cloneNode();
        $parent->replaceChildren();
        $this->assertSame([], iterator_to_array($parent->childNodes));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceChildrenWithNullAsArgOnParentHavingNoChild(Node $node, string $nodeName): void
    {
        $parent = $node->cloneNode();
        $parent->replaceChildren(null);
        $this->assertSame('', $parent->childNodes[0]->textContent);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceChildrenWithOnlyTextOnParentHavingNoChild(Node $node, string $nodeName): void
    {
        $parent = $node->cloneNode();
        $parent->replaceChildren('text');
        $this->assertSame('text', $parent->childNodes[0]->textContent);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceChildrenWithOnlyElementOnParentHavingNoChild(Node $node, string $nodeName): void
    {
        $parent = $node->cloneNode();
        $x = self::getWindow()->document->createElement('x');
        $parent->replaceChildren($x);
        $this->assertSame([$x], iterator_to_array($parent->childNodes));
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceChildrenWithNullOnParentHavingChild(Node $node, string $nodeName): void
    {
        $parent = $node->cloneNode();
        $child = self::getWindow()->document->createElement('test');
        $parent->appendChild($child);
        $parent->replaceChildren(null);
        $this->assertSame(1, $parent->childNodes->length);
        $this->assertSame('', $parent->childNodes[0]->textContent);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceChildrenWithElementAndTextOnParentHavingChild(Node $node, string $nodeName): void
    {
        $parent = $node->cloneNode();
        $x = self::getWindow()->document->createElement('x');
        $child = self::getWindow()->document->createElement('test');
        $parent->appendChild($child);
        $parent->replaceChildren($x, 'text');
        $this->assertSame(2, $parent->childNodes->length);
        $this->assertSame($x, $parent->childNodes[0]);
        $this->assertSame('text', $parent->childNodes[1]->textContent);
    }

    public function nodesProvider(): array
    {
        if (self::$testNodes !== []) {
            return self::$testNodes;
        }

        $document = self::getWindow()->document;
        self::$testNodes = [
            [$document->createElement('div'), 'Element'],
            [$document->createDocumentFragment(), 'DocumentFragment'],
        ];

        return self::$testNodes;
    }

    public static function getDocumentName(): string
    {
        return 'ParentNode-replaceChildren.html';
    }

    public function getMethodName(): string
    {
        return 'replaceChildren';
    }
}
