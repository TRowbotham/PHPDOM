<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-selectNode.html
 */
class RangeSelectNodeTest extends RangeTestCase
{
    use WindowTrait;

    private $tests;

    private static $range;
    private static $foreignRange;
    private static $xmlRange;
    private static $detachedRange;

    /**
     * @dataProvider rangeProvider
     */
    public function testSelectNode(string $marker, Range $range, Node $node): void
    {
        try {
            $range->collapsed;
        } catch (Exception $e) {
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

    public function rangeProvider(): array
    {
        $window = self::getWindow();
        $window->setupRangeTests();
        self::$range = $window->document->createRange();
        self::$foreignRange = $window->foreignDoc->createRange();
        self::$xmlRange = $window->xmlDoc->createRange();
        self::$detachedRange = $window->document->createRange();
        self::$detachedRange->detach();
        $this->tests = [];

        $this->generateTestTree($window->document, 'current doc');
        $this->generateTestTree($window->foreignDoc, 'foreign doc');
        $this->generateTestTree($window->detachedDiv, 'detached div in current doc');

        $otherTests = ['xmlDoc', 'xmlElement', 'detachedTextNode',
        'foreignTextNode', 'xmlTextNode', 'processingInstruction', 'comment',
        'foreignComment', 'xmlComment', 'docfrag', 'foreignDocfrag', 'xmlDocfrag'];

        foreach ($otherTests as $test) {
            $this->generateTestTree($window->eval($test), $test);
        }

        self::registerCleanup(function (): void {
            self::$range = null;
            self::$foreignRange = null;
            self::$xmlRange = null;
            self::$detachedRange = null;
            $this->tests = null;
        });

        return $this->tests;
    }

    public function generateTestTree(Node $root, string $marker): void
    {
        if ($root->nodeType === Node::ELEMENT_NODE && $root->id === 'log') {
            // This is being modified during the tests, so let's not test it.
            return;
        }

        $this->tests[] = [$marker, self::$range, $root];
        $this->tests[] = [$marker, self::$foreignRange, $root];
        $this->tests[] = [$marker, self::$xmlRange, $root];
        $this->tests[] = [$marker, self::$detachedRange, $root];

        foreach ($root->childNodes as $node) {
            $this->generateTestTree($node, $marker);
        }
    }

    public static function getDocumentName(): string
    {
        return 'Range-selectNode.html';
    }
}
