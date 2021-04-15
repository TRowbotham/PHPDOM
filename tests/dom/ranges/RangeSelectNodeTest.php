<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Throwable;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-selectNode.html
 */
class RangeSelectNodeTest extends RangeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider rangeProvider
     */
    public function testSelectNode(string $marker, string $rangeDoc, bool $detached, Node $node): void
    {
        $range = self::getWindow()->eval($rangeDoc)->createRange();

        if ($detached) {
            $range->detach();
        }

        try {
            $range->collapsed;
        } catch (Throwable $e) {
            // Range is detached
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNode($node);
            }, InvalidStateError::class);
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNodeContents($node);
            }, InvalidStateError::class);

            return;
        }

        if (!$node->parentNode) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNode($node);
            }, InvalidNodeTypeError::class);
        } else {
            $index = 0;

            while ($node->parentNode->childNodes[$index] !== $node) {
                ++$index;
            }

            $range->selectNode($node);

            $this->assertSame($node->parentNode, $range->startContainer);
            $this->assertSame($node->parentNode, $range->endContainer);
            $this->assertSame($index, $range->startOffset);
            $this->assertSame($index + 1, $range->endOffset);
        }

        if ($node->nodeType === Node::DOCUMENT_TYPE_NODE) {
            $this->assertThrows(static function () use ($range, $node): void {
                $range->selectNodeContents($node);
            }, InvalidNodeTypeError::class);
        } else {
            $range->selectNodeContents($node);

            $this->assertSame($node, $range->startContainer);
            $this->assertSame($node, $range->endContainer);
            $this->assertSame(0, $range->startOffset);
            $this->assertSame($node->getLength(), $range->endOffset);
        }
    }

    public function rangeProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        yield from $this->generateTestTree($window->document, 'current doc');
        yield from $this->generateTestTree($window->foreignDoc, 'foreign doc');
        yield from $this->generateTestTree($window->detachedDiv, 'detached div in current doc');

        $otherTests = ['xmlDoc', 'xmlElement', 'detachedTextNode',
        'foreignTextNode', 'xmlTextNode', 'processingInstruction', 'comment',
        'foreignComment', 'xmlComment', 'docfrag', 'foreignDocfrag', 'xmlDocfrag'];

        foreach ($otherTests as $test) {
            yield from $this->generateTestTree($window->eval($test), $test);
        }
    }

    public static function setUpBeforeClass(): void
    {
        // Don't create new nodes again because the data provider runs first.
        self::getWindow()->setupRangeTests(false);
    }

    public function generateTestTree(Node $root, string $marker): Generator
    {
        if ($root->nodeType === Node::ELEMENT_NODE && $root->id === 'log') {
            // This is being modified during the tests, so let's not test it.
            return;
        }

        yield [$marker, 'document', false, $root];
        yield [$marker, 'foreignDoc', false, $root];
        yield [$marker, 'xmlDoc', false, $root];
        yield [$marker, 'document', true, $root];

        foreach ($root->childNodes as $node) {
            yield from $this->generateTestTree($node, $marker);
        }
    }

    public static function getDocumentName(): string
    {
        return 'Range-selectNode.html';
    }
}
