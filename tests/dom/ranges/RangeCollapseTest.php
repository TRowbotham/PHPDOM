<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-collapse.html
 */
class RangeCollapseTest extends RangeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider rangeProvider
     */
    public function testCollapse(string $rangeEndpoints, ?bool $toStart): void
    {
        if ($rangeEndpoints === 'detached') {
            $range = self::getWindow()->document->createRange();
            $range->detach(); // should be a no-op and therefore the following should not throw
            $range->collapse($toStart);

            $this->assertTrue($range->collapsed);

            return;
        }

        $rangeEndpoints = self::getWindow()->eval($rangeEndpoints);
        // Have to account for Ranges involving Documents!
        $ownerDoc = $rangeEndpoints[0]->nodeType === Node::DOCUMENT_NODE
            ? $rangeEndpoints[0]
            : $rangeEndpoints[0]->ownerDocument;
        $range = $ownerDoc->createRange();
        $range->setStart($rangeEndpoints[0], $rangeEndpoints[1]);
        $range->setEnd($rangeEndpoints[2], $rangeEndpoints[3]);

        $expectedContainer = $toStart ? $range->startContainer : $range->endContainer;
        $expectedOffset = $toStart ? $range->startOffset : $range->endOffset;

        $this->assertSame($range->startContainer === $range->endContainer && $range->startOffset === $range->endOffset, $range->collapsed);

        if ($toStart === null) {
            $range->collapse();
        } else {
            $range->collapse($toStart);
        }

        $this->assertSame($expectedContainer, $range->startContainer);
        $this->assertSame($expectedContainer, $range->endContainer);
        $this->assertSame($expectedOffset, $range->startOffset);
        $this->assertSame($expectedOffset, $range->endOffset);
        $this->assertTrue($range->collapsed);
    }

    public function rangeProvider(): array
    {
        $window = self::getWindow();
        $window->initStrings();
        $tests = [];

        foreach ($window->testRanges as $i => $range) {
            $tests["Range {$i} {$range}, toStart true"] = [$range, true];
            $tests["Range {$i} {$range}, toStart false"] = [$range, false];
            $tests["Range {$i} {$range}, toStart omitted"] = [$range, null];
        }

        return $tests;
    }

    public static function setUpBeforeClass(): void
    {
        self::getWindow()->setupRangeTests();
    }

    public static function getDocumentName(): string
    {
        return 'Range-collapse.html';
    }
}
