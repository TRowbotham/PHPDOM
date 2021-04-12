<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-appendData.html
 */
class Range_mutations_appendDataTest extends RangeTestCase
{
    use Range_mutationTrait;

    public function rangeProvider(): array
    {
        $appendDataTests = [];

        foreach ($this->characterDataNodes() as $node) {
            $appendDataTests[] = [$node, '"foo"', $node, 0, $node, 1];
            $appendDataTests[] = [$node, '"foo"', $node, 0, $node, 0];
            $appendDataTests[] = [$node, '"foo"', $node, 1, $node, 1];
            $appendDataTests[] = [$node, '"foo"', $node, 0, $node, $node . "->length"];
            $appendDataTests[] = [$node, '"foo"', $node, 1, $node, $node . "->length"];
            $appendDataTests[] = [$node, '"foo"', $node, $node . "->length", $node, $node . "->length"];
            $appendDataTests[] = [$node, '"foo"', $node, 1, $node, 3];

            $appendDataTests[] = [$node, '""', $node, 0, $node, 1];
            $appendDataTests[] = [$node, '""', $node, 0, $node, 0];
            $appendDataTests[] = [$node, '""', $node, 1, $node, 1];
            $appendDataTests[] = [$node, '""', $node, 0, $node, $node . "->length"];
            $appendDataTests[] = [$node, '""', $node, 1, $node, $node . "->length"];
            $appendDataTests[] = [$node, '""', $node, $node . "->length", $node, $node . "->length"];
            $appendDataTests[] = [$node, '""', $node, 1, $node, 3];
        }

        $appendDataTests[] = ["paras[0]->firstChild", '""', "paras[0]", 0, "paras[0]", 0];
        $appendDataTests[] = ["paras[0]->firstChild", '""', "paras[0]", 0, "paras[0]", 1];
        $appendDataTests[] = ["paras[0]->firstChild", '""', "paras[0]", 1, "paras[0]", 1];
        $appendDataTests[] = ["paras[0]->firstChild", '""', "paras[0]->firstChild", 1, "paras[0]", 1];
        $appendDataTests[] = ["paras[0]->firstChild", '""', "paras[0]", 0, "paras[0]->firstChild", 3];

        $appendDataTests[] = ["paras[0]->firstChild", '"foo"', "paras[0]", 0, "paras[0]", 0];
        $appendDataTests[] = ["paras[0]->firstChild", '"foo"', "paras[0]", 0, "paras[0]", 1];
        $appendDataTests[] = ["paras[0]->firstChild", '"foo"', "paras[0]", 1, "paras[0]", 1];
        $appendDataTests[] = ["paras[0]->firstChild", '"foo"', "paras[0]->firstChild", 1, "paras[0]", 1];
        $appendDataTests[] = ["paras[0]->firstChild", '"foo"', "paras[0]", 0, "paras[0]->firstChild", 3];

        return $this->doTests($appendDataTests, static function ($params) {
            return $params[0] . ".appendData(" . $params[1] . ")";
        }, [$this, '_testAppendData']);
    }

    public function _testAppendData($node, $data, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        return $this->_testReplaceDataAlgorithm($node, $node->length, 0, $data, static function () use ($node, $data) {
            $node->appendData($data);
        }, $startContainer, $startOffset, $endContainer, $endOffset);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
