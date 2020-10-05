<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/ChildNode-replaceWith.html
 */
class ChildNodeReplaceWithTest extends TestCase
{
    private static $document;

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithoutArgs($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->replaceWith();

        $this->assertSame('', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithNull($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->replaceWith(null);

        $this->assertSame('null', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithEmptyString($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->replaceWith('');

        $this->assertSame('', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithOnlyText($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->replaceWith('text');

        $this->assertSame('text', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithOneElement($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $child->replaceWith($x);

        $this->assertSame('<x></x>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithSiblingOfChild($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $z = self::$document->createElement('z');
        $parent->appendChild($y);
        $parent->appendChild($child);
        $parent->appendChild($x);
        $child->replaceWith($x, $y, $z);

        $this->assertSame('<x></x><y></y><z></z>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithSiblingOfChildAndText($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $parent->appendChild($x);
        $parent->appendChild(self::$document->createTextNode('1'));
        $child->replaceWith($x, '2');

        $this->assertSame('<x></x>21', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithSiblingOfChildAndChild($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $parent->appendChild($x);
        $parent->appendChild(self::$document->createTextNode('text'));
        $child->replaceWith($x, $child);

        $this->assertSame('<x></x>' . $innerHTML . 'text', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithWithOneElementAndText($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $child->replaceWith($x, 'text');

        $this->assertSame('<x></x>text', $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testReplaceWithOnParentlessChildWithTwoElements($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $parent->appendChild($x);
        $parent->appendChild($y);
        $child->replaceWith($x, $y);

        $this->assertSame('<x></x><y></y>', $parent->innerHTML);
    }

    public function nodesProvider(): array
    {
        if (!self::$document) {
            self::$document = new HTMLDocument();
        }

        return [
            [self::$document->createComment('test'), 'Comment', '<!--test-->'],
            [self::$document->createElement('test'), 'Element', '<test></test>'],
            [self::$document->createTextNode('test'), 'Text', 'test'],
        ];
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
