<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-deleteData.html
 */
class Range_mutations_deleteDataTest extends RangeTestCase
{
    use Range_mutationTrait;

    public function rangeProvider(): array
    {
        $deleteDataTests = [];

        foreach ($this->characterDataNodes() as $node) {
            $deleteDataTests[] = [$node, 376, 2, $node, 0, $node, 1];
            $deleteDataTests[] = [$node, 0, 2, $node, 0, $node, 0];
            $deleteDataTests[] = [$node, 1, 2, $node, 1, $node, 1];
            $deleteDataTests[] = [$node, $node . "->length", 2, $node, $node . "->length", $node, $node . "->length"];
            $deleteDataTests[] = [$node, 1, 2, $node, 1, $node, 3];
            $deleteDataTests[] = [$node, 2, 2, $node, 1, $node, 3];
            $deleteDataTests[] = [$node, 3, 2, $node, 1, $node, 3];

            $deleteDataTests[] = [$node, 376, 0, $node, 0, $node, 1];
            $deleteDataTests[] = [$node, 0, 0, $node, 0, $node, 0];
            $deleteDataTests[] = [$node, 1, 0, $node, 1, $node, 1];
            $deleteDataTests[] = [$node, $node . "->length", 0, $node, $node . "->length", $node, $node . "->length"];
            $deleteDataTests[] = [$node, 1, 0, $node, 1, $node, 3];
            $deleteDataTests[] = [$node, 2, 0, $node, 1, $node, 3];
            $deleteDataTests[] = [$node, 3, 0, $node, 1, $node, 3];

            $deleteDataTests[] = [$node, 376, 631, $node, 0, $node, 1];
            $deleteDataTests[] = [$node, 0, 631, $node, 0, $node, 0];
            $deleteDataTests[] = [$node, 1, 631, $node, 1, $node, 1];
            $deleteDataTests[] = [$node, $node . "->length", 631, $node, $node . "->length", $node, $node . "->length"];
            $deleteDataTests[] = [$node, 1, 631, $node, 1, $node, 3];
            $deleteDataTests[] = [$node, 2, 631, $node, 1, $node, 3];
            $deleteDataTests[] = [$node, 3, 631, $node, 1, $node, 3];
        }

        $deleteDataTests[] = ["paras[0]->firstChild", 1, 2, "paras[0]", 0, "paras[0]", 0];
        $deleteDataTests[] = ["paras[0]->firstChild", 1, 2, "paras[0]", 0, "paras[0]", 1];
        $deleteDataTests[] = ["paras[0]->firstChild", 1, 2, "paras[0]", 1, "paras[0]", 1];
        $deleteDataTests[] = ["paras[0]->firstChild", 1, 2, "paras[0]->firstChild", 1, "paras[0]", 1];
        $deleteDataTests[] = ["paras[0]->firstChild", 2, 2, "paras[0]->firstChild", 1, "paras[0]", 1];
        $deleteDataTests[] = ["paras[0]->firstChild", 3, 2, "paras[0]->firstChild", 1, "paras[0]", 1];
        $deleteDataTests[] = ["paras[0]->firstChild", 1, 2, "paras[0]", 0, "paras[0]->firstChild", 3];
        $deleteDataTests[] = ["paras[0]->firstChild", 2, 2, "paras[0]", 0, "paras[0]->firstChild", 3];
        $deleteDataTests[] = ["paras[0]->firstChild", 3, 2, "paras[0]", 0, "paras[0]->firstChild", 3];

        return $this->doTests($deleteDataTests, static function ($params) {
            return $params[0] . ".deleteData(" . $params[1] . ", " . $params[2] . ")";
        }, [$this, '_testDeleteData']);
    }

    public function _testDeleteData($node, $offset, $count, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        return $this->_testReplaceDataAlgorithm($node, $offset, $count, "", static function () use ($node, $count, $offset) {
            $node->deleteData($offset, $count);
        }, $startContainer, $startOffset, $endContainer, $endOffset);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
