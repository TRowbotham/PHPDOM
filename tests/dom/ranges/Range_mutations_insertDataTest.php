<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-insertData.html
 */
class Range_mutations_insertDataTest extends RangeTestCase
{
    use Range_mutationTrait;

    public function rangeProvider(): array
    {
        $insertDataTests = [];

        foreach ($this->characterDataNodes() as $node) {
            $insertDataTests[] = [$node, 376, '"foo"', $node, 0, $node, 1];
            $insertDataTests[] = [$node, 0, '"foo"', $node, 0, $node, 0];
            $insertDataTests[] = [$node, 1, '"foo"', $node, 1, $node, 1];
            $insertDataTests[] = [$node, $node . "->length", '"foo"', $node, $node . "->length", $node, $node . "->length"];
            $insertDataTests[] = [$node, 1, '"foo"', $node, 1, $node, 3];
            $insertDataTests[] = [$node, 2, '"foo"', $node, 1, $node, 3];
            $insertDataTests[] = [$node, 3, '"foo"', $node, 1, $node, 3];

            $insertDataTests[] = [$node, 376, '""', $node, 0, $node, 1];
            $insertDataTests[] = [$node, 0, '""', $node, 0, $node, 0];
            $insertDataTests[] = [$node, 1, '""', $node, 1, $node, 1];
            $insertDataTests[] = [$node, $node . "->length", '""', $node, $node . "->length", $node, $node . "->length"];
            $insertDataTests[] = [$node, 1, '""', $node, 1, $node, 3];
            $insertDataTests[] = [$node, 2, '""', $node, 1, $node, 3];
            $insertDataTests[] = [$node, 3, '""', $node, 1, $node, 3];
        }

        $insertDataTests[] = ["paras[0]->firstChild", 1, '"foo"', "paras[0]", 0, "paras[0]", 0];
        $insertDataTests[] = ["paras[0]->firstChild", 1, '"foo"', "paras[0]", 0, "paras[0]", 1];
        $insertDataTests[] = ["paras[0]->firstChild", 1, '"foo"', "paras[0]", 1, "paras[0]", 1];
        $insertDataTests[] = ["paras[0]->firstChild", 1, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $insertDataTests[] = ["paras[0]->firstChild", 2, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $insertDataTests[] = ["paras[0]->firstChild", 3, '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $insertDataTests[] = ["paras[0]->firstChild", 1, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $insertDataTests[] = ["paras[0]->firstChild", 2, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];
        $insertDataTests[] = ["paras[0]->firstChild", 3, '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];

        return $this->doTests($insertDataTests, static function ($params) {
            return $params[0] . ".insertData(" . $params[1] . ", " . $params[2] . ")";
        }, [$this, '_testInsertData']);
    }

    public function _testInsertData($node, $offset, $data, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        return $this->_testReplaceDataAlgorithm($node, $offset, 0, $data, static function () use ($node, $data, $offset) {
            $node->insertData($offset, $data);
        }, $startContainer, $startOffset, $endContainer, $endOffset);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
