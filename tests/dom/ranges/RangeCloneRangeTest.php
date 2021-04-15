<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-cloneRange.html
 */
class RangeCloneRangeTest extends RangeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider rangeProvider
     */
    public function testCloneRange(string $rangeEndpoints): void
    {
        if ($rangeEndpoints === 'detached') {
            $range = self::getWindow()->document->createRange();
            $range->detach();
            $clonedRange = $range->cloneRange();

            $this->assertSame($range->startContainer, $clonedRange->startContainer);
            $this->assertSame($range->startOffset, $clonedRange->startOffset);
            $this->assertSame($range->endContainer, $clonedRange->endContainer);
            $this->assertSame($range->endOffset, $clonedRange->endOffset);

            return;
        }

        $rangeEndpoints = self::getWindow()->eval($rangeEndpoints);
        $ownerDoc = $rangeEndpoints[0]->nodeType === Node::DOCUMENT_NODE
            ? $rangeEndpoints[0]
            : $rangeEndpoints[0]->ownerDocument;
        $range = $ownerDoc->createRange();

        // Here we throw in some createRange() tests, because why not.  Have to
        // test it someplace.
        $this->assertSame($ownerDoc, $range->startContainer);
        $this->assertSame($ownerDoc, $range->endContainer);
        $this->assertSame(0, $range->startOffset);
        $this->assertSame(0, $range->endOffset);

        $range->setStart($rangeEndpoints[0], $rangeEndpoints[1]);
        $range->setEnd($rangeEndpoints[2], $rangeEndpoints[3]);

        // Make sure we bail out now if setStart or setEnd are buggy, so it doesn't
        // create misleading failures later.
        $this->assertSame($rangeEndpoints[0], $range->startContainer);
        $this->assertSame($rangeEndpoints[1], $range->startOffset);
        $this->assertSame($rangeEndpoints[2], $range->endContainer);
        $this->assertSame($rangeEndpoints[3], $range->endOffset);

        $clonedRange = $range->cloneRange();

        $this->assertSame($range->startContainer, $clonedRange->startContainer);
        $this->assertSame($range->startOffset, $clonedRange->startOffset);
        $this->assertSame($range->endContainer, $clonedRange->endContainer);
        $this->assertSame($range->endOffset, $clonedRange->endOffset);

        // Make sure that modifying one doesn't affect the other.
        $testNode1 = $ownerDoc->createTextNode('testing');
        $testNode2 = $ownerDoc->createTextNode('testing with different length');

        $range->setStart($testNode1, 1);
        $range->setEnd($testNode1, 2);

        $this->assertSame($rangeEndpoints[0], $clonedRange->startContainer);
        $this->assertSame($rangeEndpoints[1], $clonedRange->startOffset);
        $this->assertSame($rangeEndpoints[2], $clonedRange->endContainer);
        $this->assertSame($rangeEndpoints[3], $clonedRange->endOffset);

        $clonedRange->setStart($testNode2, 3);
        $clonedRange->setEnd($testNode2, 4);

        $this->assertSame($testNode1, $range->startContainer);
        $this->assertSame(1, $range->startOffset);
        $this->assertSame($testNode1, $range->endContainer);
        $this->assertSame(2, $range->endOffset);
    }

    public function rangeProvider(): array
    {
        $window = self::getWindow();
        $window->initStrings();
        $tests = [];

        foreach ($window->testRanges as $i => $range) {
            $tests["Range {$i} {$range}"] = [$range];
        }

        return $tests;
    }

    public static function setUpBeforeClass(): void
    {
        self::getWindow()->setupRangeTests();
    }

    public static function getDocumentName(): string
    {
        return 'Range-cloneRange.html';
    }
}
