<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use function array_merge;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-removeChild.html
 */
class Range_mutations_removeChildTest extends RangeTestCase
{
    use Range_mutationTrait;

    private const REMOVE_CHILD_TESTS = [
        ["paras[0]", "paras[0]", 0, "paras[0]", 0],
        ["paras[0]", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "paras[0]", 1, "paras[0]", 1],
        ["paras[0]", "testDiv", 0, "testDiv", 0],
        ["paras[0]", "testDiv", 0, "testDiv", 1],
        ["paras[0]", "testDiv", 1, "testDiv", 1],
        ["paras[0]", "testDiv", 0, "testDiv", 2],
        ["paras[0]", "testDiv", 1, "testDiv", 2],
        ["paras[0]", "testDiv", 2, "testDiv", 2],

        ["foreignDoc->documentElement", "foreignDoc", 0, "foreignDoc", "foreignDoc->childNodes->length"],
    ];

    public function rangeProvider(): array
    {
        return $this->doTests(self::REMOVE_CHILD_TESTS, static function ($params) {
            return $params[0] . ".removeChild(" . $params[1] . ")";
        }, [$this, '_testRemoveChild']);
    }

    public function _testRemoveChild($affectedNode, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        $expectedStart = [$startContainer, $startOffset];
        $expectedEnd = [$endContainer, $endOffset];

        $expectedStart = $this->modifyForRemove($affectedNode, $expectedStart);
        $expectedEnd = $this->modifyForRemove($affectedNode, $expectedEnd);

        $affectedNode->parentNode->removeChild($affectedNode);

        return array_merge($expectedStart, $expectedEnd);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
