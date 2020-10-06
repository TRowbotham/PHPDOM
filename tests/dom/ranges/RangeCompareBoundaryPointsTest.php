<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Generator;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function abs;
use function array_push;
use function array_search;
use function count;
use function floor;
use function in_array;

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
    public function testCompareBoundaryPoints(Range $range1, int $i, Range $range2, int $j): void
    {
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
            $this->assertNotNull($range1);
            $this->assertNotNull($range2);

            $convertedHow = $how + 0;

            if (
                $convertedHow === NAN
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
                $this->assertThrows(static function () use ($range1, $range2, $how): void {
                    $range1->compareBoundaryPoints($how, $range2);
                }, NotSupportedError::class);

                return;
            }

            if (Window::furthestAncestor($range1->startContainer) !== Window::furthestAncestor($range2->startContainer)) {
                $this->assertThrows(static function () use ($range1, $range2, $how): void {
                    $range1->compareBoundaryPoints($how, $range2);
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

            $this->assertSame($expected, $range1->compareBoundaryPoints($how, $range2));
        }
    }

    public function rangeProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        $testRangesCached = [];
        $testRangesCached[] = $window->document->createRange();

        foreach ($window->testRangesShort as $endpoints) {
            try {
                $testRangesCached[] = Window::rangeFromEndpoints($window->eval($endpoints));
            } catch (Exception $e) {
                $testRangesCached[] = null;
            }
        }

        $testRangesCachedClones = [];
        $testRangesCachedClones[] = $window->document->createRange();
        $testRangesCachedClones[0]->detach();

        foreach ($testRangesCached as $range) {
            if ($range !== null) {
                $testRangesCachedClones[] = $range->cloneRange();
            } else {
                $testRangesCachedClones[] = null;
            }
        }

        self::$extraTests = [
            0, // detached
            1 + array_search('[paras[0]->firstChild, 2, paras[0]->firstChild, 8]', $window->testRanges, true),
            1 + array_search('[paras[0]->firstChild, 3, paras[3], 1]', $window->testRanges, true),
            1 + array_search('[testDiv, 0, comment, 5]', $window->testRanges, true),
            1 + array_search('[foreignDoc->documentElement, 0, foreignDoc->documentElement, 1]', $window->testRanges, true),
        ];

        $length = count($testRangesCachedClones);

        self::registerCleanup(static function (): void {
            self::$extraTests = null;
        });

        foreach ($testRangesCached as $i => $range1) {
            foreach ($testRangesCachedClones as $j => $range2) {
                if ($j === $length) {
                    $range2 = $range1;
                }

                yield [$range1, $i, $range2, $j];
            }
        }
    }

    public static function getDocumentName(): string
    {
        return 'Range-compareBoundaryPoints.html';
    }
}
