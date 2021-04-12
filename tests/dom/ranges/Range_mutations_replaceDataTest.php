<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-replaceData.html
 */
class Range_mutations_replaceDataTest extends RangeTestCase
{
    use Range_mutationTrait;

    public function rangeProvider(): array
    {
        $replaceDataTests = [];

        foreach ($this->characterDataNodes() as $node) {
            $replaceDataTests[] = [$node, 376, 0, '"foo"', $node, 0, $node, 1];
            $replaceDataTests[] = [$node, 0, 0, '"foo"', $node, 0, $node, 0];
            $replaceDataTests[] = [$node, 1, 0, '"foo"', $node, 1, $node, 1];
            $replaceDataTests[] = [$node, $node . "->length", 0, '"foo"', $node, $node . "->length", $node, $node . "->length"];
            $replaceDataTests[] = [$node, 1, 0, '"foo"', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 2, 0, '"foo"', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 3, 0, '"foo"', $node, 1, $node, 3];

            $replaceDataTests[] = [$node, 376, 0, '""', $node, 0, $node, 1];
            $replaceDataTests[] = [$node, 0, 0, '""', $node, 0, $node, 0];
            $replaceDataTests[] = [$node, 1, 0, '""', $node, 1, $node, 1];
            $replaceDataTests[] = [$node, $node . "->length", 0, '""', $node, $node . "->length", $node, $node . "->length"];
            $replaceDataTests[] = [$node, 1, 0, '""', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 2, 0, '""', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 3, 0, '""', $node, 1, $node, 3];

            $replaceDataTests[] = [$node, 376, 1, '"foo"', $node, 0, $node, 1];
            $replaceDataTests[] = [$node, 0, 1, '"foo"', $node, 0, $node, 0];
            $replaceDataTests[] = [$node, 1, 1, '"foo"', $node, 1, $node, 1];
            $replaceDataTests[] = [$node, $node . "->length", 1, '"foo"', $node, $node . "->length", $node, $node . "->length"];
            $replaceDataTests[] = [$node, 1, 1, '"foo"', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 2, 1, '"foo"', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 3, 1, '"foo"', $node, 1, $node, 3];

            $replaceDataTests[] = [$node, 376, 1, '""', $node, 0, $node, 1];
            $replaceDataTests[] = [$node, 0, 1, '""', $node, 0, $node, 0];
            $replaceDataTests[] = [$node, 1, 1, '""', $node, 1, $node, 1];
            $replaceDataTests[] = [$node, $node . "->length", 1, '""', $node, $node . "->length", $node, $node . "->length"];
            $replaceDataTests[] = [$node, 1, 1, '""', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 2, 1, '""', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 3, 1, '""', $node, 1, $node, 3];

            $replaceDataTests[] = [$node, 376, 47, '"foo"', $node, 0, $node, 1];
            $replaceDataTests[] = [$node, 0, 47, '"foo"', $node, 0, $node, 0];
            $replaceDataTests[] = [$node, 1, 47, '"foo"', $node, 1, $node, 1];
            $replaceDataTests[] = [$node, $node . "->length", 47, '"foo"', $node, $node . "->length", $node, $node . "->length"];
            $replaceDataTests[] = [$node, 1, 47, '"foo"', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 2, 47, '"foo"', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 3, 47, '"foo"', $node, 1, $node, 3];

            $replaceDataTests[] = [$node, 376, 47, '""', $node, 0, $node, 1];
            $replaceDataTests[] = [$node, 0, 47, '""', $node, 0, $node, 0];
            $replaceDataTests[] = [$node, 1, 47, '""', $node, 1, $node, 1];
            $replaceDataTests[] = [$node, $node . "->length", 47, '""', $node, $node . "->length", $node, $node . "->length"];
            $replaceDataTests[] = [$node, 1, 47, '""', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 2, 47, '""', $node, 1, $node, 3];
            $replaceDataTests[] = [$node, 3, 47, '""', $node, 1, $node, 3];
        }

        $replaceDataTests[] = ["paras[0]->firstChild", 1, 0, '"foo"', "paras[0]", 0, "paras[0]", 0];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 0, '"foo"', "paras[0]", 0, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 0, '"foo"', "paras[0]", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 0, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 2, 0, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 3, 0, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 0, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $replaceDataTests[] = ["paras[0]->firstChild", 2, 0, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $replaceDataTests[] = ["paras[0]->firstChild", 3, 0, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];

        $replaceDataTests[] = ["paras[0]->firstChild", 1, 1, '"foo"', "paras[0]", 0, "paras[0]", 0];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 1, '"foo"', "paras[0]", 0, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 1, '"foo"', "paras[0]", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 1, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 2, 1, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 3, 1, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 1, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $replaceDataTests[] = ["paras[0]->firstChild", 2, 1, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $replaceDataTests[] = ["paras[0]->firstChild", 3, 1, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];

        $replaceDataTests[] = ["paras[0]->firstChild", 1, 47, '"foo"', "paras[0]", 0, "paras[0]", 0];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 47, '"foo"', "paras[0]", 0, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 47, '"foo"', "paras[0]", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 47, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 2, 47, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 3, 47, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $replaceDataTests[] = ["paras[0]->firstChild", 1, 47, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $replaceDataTests[] = ["paras[0]->firstChild", 2, 47, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $replaceDataTests[] = ["paras[0]->firstChild", 3, 47, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];

        return $this->doTests($replaceDataTests, static function ($params) {
            return $params[0] . ".replaceData(" . $params[1] . ", " . $params[2] . ", " . $params[3] . ")";
        }, [$this, '_testReplaceData']);
    }

    public function _testReplaceData($node, $offset, $count, $data, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        return $this->_testReplaceDataAlgorithm($node, $offset, $count, $data, static function () use ($node, $data, $offset, $count) {
            $node->replaceData($offset, $count, $data);
        }, $startContainer, $startOffset, $endContainer, $endOffset);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
