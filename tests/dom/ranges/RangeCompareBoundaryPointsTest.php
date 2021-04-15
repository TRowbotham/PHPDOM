<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Throwable;

use function abs;
use function array_push;
use function array_search;
use function array_unshift;
use function count;
use function floor;
use function in_array;
use function is_nan;
use function is_numeric;

use const INF;
use const NAN;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-compareBoundaryPoints.html
 */
class RangeCompareBoundaryPointsTest extends RangeTestCase
{
    use WindowTrait;

    private static $extraTests;

    /**
     * @dataProvider rangeProvider
     */
    public function testCompareBoundaryPoints(string $range, int $i, $how): void
    {
        $window = self::getWindow();

        try {
            if ($range === '[detached]') {
                $range1 = $window->document->createRange();
                $range1->detach();
            } else {
                $range1 = Window::rangeFromEndpoints($window->eval($range));
            }
        } catch (Throwable $e) {
            $range1 = null;
        }

        if ($range1 !== null) {
            if ($i === 0) {
                $range2 = $window->document->createRange();
                $range2->detach();
            } else {
                $range2 = $range1->cloneRange();
            }
        } else {
            $range2 = null;
        }

        $this->assertNotNull($range1);
        $this->assertNotNull($range2);

        $convertedHow = $this->toNumber($how);

        if (
            is_nan($convertedHow)
            || $convertedHow === 0
            || $convertedHow === INF
            || $convertedHow === -INF
        ) {
            $convertedHow = 0;
        } else {
            // "Let posInt be sign(number) * floor(abs(number))."
            $posInt = ($convertedHow < 0 ? -1 : 1) * floor(abs($convertedHow));

            // "Let int16bit be posInt modulo 2^16; that is, a finite
            // integer value k of Number type with positive sign and
            // less than 2^16 in magnitude such that the mathematical
            // difference of posInt and k is mathematically an integer
            // multiple of 2^16."
            //
            // "Return int16bit."
            $convertedHow = $posInt % 65536;

            if ($convertedHow < 0) {
                $convertedHow += 65536;
            }
        }

        // Now to the actual algorithm.
        // "If how is not one of
        //   START_TO_START,
        //   START_TO_END,
        //   END_TO_END, and
        //   END_TO_START,
        // throw a "NotSupportedError" exception and terminate these
        // steps."
        if (
            $convertedHow !== Range::START_TO_START
            && $convertedHow !== Range::START_TO_END
            && $convertedHow !== Range::END_TO_END
            && $convertedHow !== Range::END_TO_START
        ) {
            // Use $convertedHow instead of $how since PHP and JS differ in how values are cast to a number
            $this->assertThrows(static function () use ($range1, $range2, $convertedHow): void {
                $range1->compareBoundaryPoints($convertedHow, $range2);
            }, NotSupportedError::class);

            return;
        }

        if (Window::furthestAncestor($range1->startContainer) !== Window::furthestAncestor($range2->startContainer)) {
            // Use $convertedHow instead of $how since PHP and JS differ in how values are cast to a number
            $this->assertThrows(static function () use ($range1, $range2, $convertedHow): void {
                $range1->compareBoundaryPoints($convertedHow, $range2);
            }, WrongDocumentError::class);

            return;
        }

        // "If how is:
        //   START_TO_START:
        //     Let this point be the context object's start.
        //     Let other point be sourceRange's start.
        //   START_TO_END:
        //     Let this point be the context object's end.
        //     Let other point be sourceRange's start.
        //   END_TO_END:
        //     Let this point be the context object's end.
        //     Let other point be sourceRange's end.
        //   END_TO_START:
        //     Let this point be the context object's start.
        //     Let other point be sourceRange's end."
        $thisPoint = $convertedHow === Range::START_TO_START || $convertedHow === Range::END_TO_START
            ? [$range1->startContainer, $range1->startOffset]
            : [$range1->endContainer, $range1->endOffset];
        $otherPoint = $convertedHow === Range::START_TO_START || $convertedHow === Range::START_TO_END
            ? [$range2->startContainer, $range2->startOffset]
            : [$range2->endContainer, $range2->endOffset];

        // "If the position of this point relative to other point is
        //   before
        //     Return −1.
        //   equal
        //     Return 0.
        //   after
        //     Return 1."
        $position = Window::getPosition($thisPoint[0], $thisPoint[1], $otherPoint[0], $otherPoint[1]);

        if ($position === "before") {
            $expected = -1;
        } elseif ($position === "equal") {
            $expected = 0;
        } elseif ($position === "after") {
            $expected = 1;
        }

        // Use $convertedHow instead of $how since PHP and JS differ in how values are cast to a number
        $this->assertSame($expected, $range1->compareBoundaryPoints($convertedHow, $range2));
    }

    public function rangeProvider(): Generator
    {
        $window = self::getWindow();
        $window->initStrings();
        array_unshift($window->testRangesShort, '[detached]');

        self::$extraTests = [
            0, // detached
            1 + array_search('[paras[0]->firstChild, 2, paras[0]->firstChild, 8]', $window->testRanges, true),
            1 + array_search('[paras[0]->firstChild, 3, paras[3], 1]', $window->testRanges, true),
            1 + array_search('[testDiv, 0, comment, 5]', $window->testRanges, true),
            1 + array_search('[foreignDoc->documentElement, 0, foreignDoc->documentElement, 1]', $window->testRanges, true),
        ];
        $length = count($window->testRangesShort);

        foreach ($window->testRangesShort as $i => $range1) {
            foreach ($window->testRangesShort as $j => $range2) {
                $hows = [
                    Range::START_TO_START,
                    Range::START_TO_END,
                    Range::END_TO_END,
                    Range::END_TO_START,
                ];

                if (in_array($i, self::$extraTests, true) && in_array($j, self::$extraTests, true)) {
                    // TODO: Make some type of reusable utility function to do this work.
                    array_push($hows, -1, 4, 5, NAN, -0, +INF, -INF);

                    foreach ([65536, -65536, 65536 * 65536, 0.5, -0.5, -72.5] as $addend) {
                        array_push($hows, -1 + $addend, 0 + $addend, 1 + $addend, 2 + $addend, 3 + $addend, 4 + $addend);
                    }

                    foreach ($hows as $how) {
                        $hows[] = (string) $how;
                    }

                    array_push($hows, "6.5536e4", null, true, false, "", "quasit");
                }

                foreach ($hows as $how) {
                    if ($j === $length) {
                        $range1 = $range2;
                    }

                    yield [$range1, $i, $how];
                }
            }
        }
    }

    /**
     * @param int|string|\NAN|\INF|bool|null $input
     *
     * @return int|\NAN|\INF
     */
    public function toNumber($input)
    {
        if ($input === NAN || $input === 'NAN') {
            return NAN;
        }

        if ($input === -INF || $input === '-INF') {
            return -INF;
        }

        if ($input === +INF || $input === INF || $input === '+INF' || $input === 'INF') {
            return INF;
        }

        if (is_numeric($input) || $input === null || $input === true || $input === false) {
            return (int) $input;
        }

        return NAN;
    }

    public static function setUpBeforeClass(): void
    {
        $window = self::getWindow();
        $window->setupRangeTests();
        array_unshift($window->testRangesShort, '[detached]');
    }

    public static function getDocumentName(): string
    {
        return 'Range-compareBoundaryPoints.html';
    }
}
