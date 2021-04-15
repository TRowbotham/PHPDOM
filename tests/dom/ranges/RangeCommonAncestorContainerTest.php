<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Generator;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function array_unshift;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-commonAncestorContainer.html
 */
class RangeCommonAncestorContainerTest extends RangeTestCase
{
    use WindowTrait;

    /**
     * @dataProvider rangeProvider
     */
    public function testRanges(int $i, string $endpoints): void
    {
        $window = self::getWindow();

        if ($i === 0) {
            $range = $window->document->createRange();
            $range->detach();
        } else {
            $range = Window::rangeFromEndpoints($window->eval($endpoints));
        }

        // "Let container be start node."
        $container = $range->startContainer;

        // "While container is not an inclusive ancestor of end node, let
        // container be container's parent."
        while ($container !== $range->endContainer && !Window::isAncestor($container, $range->endContainer)) {
            $container = $container->parentNode;
        }

        $this->assertSame($container, $range->commonAncestorContainer);
    }

    public function rangeProvider(): Generator
    {
        $window = self::getWindow();
        $window->initStrings();
        array_unshift($window->testRanges, '[detached]');

        foreach ($window->testRanges as $i => $range) {
            yield [$i, $range];
        }
    }

    public static function setUpBeforeClass(): void
    {
        $window = self::getWindow();
        $window->setupRangeTests();
        array_unshift($window->testRanges, '[detached]');
    }

    public static function getDocumentName(): string
    {
        return 'Range-commonAncestorContainer.html';
    }
}
