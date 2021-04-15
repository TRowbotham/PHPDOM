<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/ChildNode-before.html
 */
class ChildNodeBeforeTest extends TestCase
{
    private static $document;

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithoutArgument($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->before();

        $this->assertSame($innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithNull($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->before(null);

        $this->assertSame('null' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithEmptyString($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->before('');

        $this->assertSame('', $parent->firstChild->data);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithTextOnly($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->before('text');

        $this->assertSame('text' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithOneElement($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $child->before($x);

        $this->assertSame('<x></x>' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithOneElementAndText($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $child->before($x, 'text');

        $this->assertSame('<x></x>text' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithContextObject($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->before('text', $child);

        $this->assertSame('text' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithContextObjectSwitching($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $parent->appendChild($x);
        $child->before($x, $child);

        $this->assertSame('<x></x>' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithSiblingsOfChild($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $z = self::$document->createElement('z');
        $parent->appendChild($y);
        $parent->appendChild($child);
        $parent->appendChild($x);
        $child->before($x, $y, $z);

        $this->assertSame('<x></x><y></y><z></z>' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithSomeSiblings1($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $z = self::$document->createElement('z');
        $parent->appendChild($x);
        $parent->appendChild($y);
        $parent->appendChild($z);
        $parent->appendChild($child);
        $child->before($y, $z);

        $this->assertSame('<x></x><y></y><z></z>' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithSomeSiblings2($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $v = self::$document->createElement('v');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $z = self::$document->createElement('z');
        $parent->appendChild($v);
        $parent->appendChild($x);
        $parent->appendChild($y);
        $parent->appendChild($z);
        $parent->appendChild($child);
        $child->before($y, $z);

        $this->assertSame('<v></v><x></x><y></y><z></z>' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWhenPreinsertBehavesLikeAppend($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $parent->appendChild($x);
        $parent->appendChild($y);
        $parent->appendChild($child);
        $child->before($y, $x);

        $this->assertSame('<y></y><x></x>' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodesProvider
     */
    public function testBeforeWithOneSiblingAndTest($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($x);
        $parent->appendChild(self::$document->createTextNode('1'));
        $y = self::$document->createElement('y');
        $parent->appendChild($y);
        $parent->appendChild($child);
        $child->before($x, '2');

        $this->assertSame('1<y></y><x></x>2' . $innerHTML, $parent->innerHTML);
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
