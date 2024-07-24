<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use RuntimeException;

use function array_merge;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-dataChange.html
 */
class Range_mutations_dataChangeTest extends RangeTestCase
{
    use Range_mutationTrait;

    public static function rangeProvider(): array
    {
        $dataChangeTests = [];
        $dataChangeTestAttrs = ["data", "textContent", "nodeValue"];

        foreach (self::characterDataNodes() as $node) {
            $dataChangeTestRanges = [
                [$node, 0, $node, 0],
                [$node, 0, $node, 1],
                [$node, 1, $node, 1],
                [$node, 0, $node, $node . "->length"],
                [$node, 1, $node, $node . "->length"],
                [$node, $node . "->length", $node, $node . "->length"],
            ];

            foreach ($dataChangeTestRanges as $ranges) {
                foreach ($dataChangeTestAttrs as $attr) {
                    $dataChangeTests[] = array_merge([
                        $node,
                        '"' . $attr . '"',
                        '"="',
                        '""',
                    ], $ranges);

                    $dataChangeTests[] = array_merge([
                        $node,
                        '"' . $attr . '"',
                        '"="',
                        '"foo"',
                    ], $ranges);

                    $dataChangeTests[] = array_merge([
                        $node,
                        '"' . $attr . '"',
                        '"+="',
                        $node . "->" . $attr,
                    ], $ranges);

                    $dataChangeTests[] = array_merge([
                        $node,
                        '"' . $attr . '"',
                        '"+="',
                        '"foo"',
                    ], $ranges);

                    $dataChangeTests[] = array_merge([
                        $node,
                        '"' . $attr . '"',
                        '"+="',
                        $node . "->" . $attr,
                    ], $ranges);
                }
            }
        }

        return self::doTests($dataChangeTests, static function ($params) {
            return $params[0] . ".appendData(" . $params[1] . ")";
        }, self::_testDataChange(...));
    }

    public static function _testDataChange($node, $attr, $op, $rval, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        return self::_testReplaceDataAlgorithm($node, 0, $node->length, $op === '=' ? $rval : $node->{$attr} . $rval, static function () use ($node, $op, $attr, $rval) {
            if ($op === '=') {
                $node->{$attr} = $rval;
            } elseif ($op === '+=') {
                $node->{$attr} .= $rval;
            } else {
                throw new RuntimeException('Unknown op ' . $op);
            }
        }, $startContainer, $startOffset, $endContainer, $endOffset);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
