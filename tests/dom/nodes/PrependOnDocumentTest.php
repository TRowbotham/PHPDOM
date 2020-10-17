<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\dom\DocumentGetter;

use function iterator_to_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/prepend-on-Document.html
 */
class PrependOnDocumentTest extends NodeTestCase
{
    use DocumentGetter;

    private static $document;
    private static $node;

    public function testPrependWithoutAnyArgsOnDocumentHavingNoChild(): void
    {
        $parent = self::$node->cloneNode();
        $parent->prepend();
        $this->assertSame([], iterator_to_array($parent->childNodes));
    }

    public function testPrependWithOneElementOnDocumentHavingNoChild(): void
    {
        $parent = self::$node->cloneNode();
        $x = self::$document->createElement('x');
        $parent->prepend($x);
        $this->assertSame([$x], iterator_to_array($parent->childNodes));
    }

    public function testPrependWithOneElementOnDocumentHavingOneChild(): void
    {
        $parent = self::$node->cloneNode();
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $parent->appendChild($x);

        $this->assertThrows(static function () use ($parent, $y): void {
            $parent->prepend($y);
        }, HierarchyRequestError::class);
        $this->assertSame([$x], iterator_to_array($parent->childNodes));
    }

    public function testPrependWithTextOnDocumentHavingNoChild(): void
    {
        $parent = self::$node->cloneNode();

        $this->assertThrows(static function () use ($parent): void {
            $parent->prepend('text');
        }, HierarchyRequestError::class);
        $this->assertSame([], iterator_to_array($parent->childNodes));
    }

    public function testPrependWithTwoElementsOnDocumentHavingNoChild(): void
    {
        $parent = self::$node->cloneNode();
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $parent->appendChild($x);

        $this->assertThrows(static function () use ($parent, $x, $y): void {
            $parent->prepend($x, $y);
        }, HierarchyRequestError::class);
        $this->assertSame([], iterator_to_array($parent->childNodes));
    }

    public static function setUpBeforeClass(): void
    {
        self::$document = new HTMLDocument();
        self::$node = self::$document->implementation->createDocument(null, null);
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
        self::$node = null;
    }
}
