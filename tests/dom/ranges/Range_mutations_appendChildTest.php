<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Exception\DOMException;

use function array_merge;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-appendChild.html
 */
class Range_mutations_appendChildTest extends RangeTestCase
{
    use Range_mutationTrait;

    private const APPEND_CHILD_TESTS = [
        // Moving a node to its current position
        ["testDiv", "testDiv->lastChild", "testDiv->lastChild", 0, "testDiv->lastChild", 0],
        ["testDiv", "testDiv->lastChild", "testDiv->lastChild", 0, "testDiv->lastChild", 1],
        ["testDiv", "testDiv->lastChild", "testDiv->lastChild", 1, "testDiv->lastChild", 1],
        ["testDiv", "testDiv->lastChild", "testDiv", "testDiv->childNodes->length - 2", "testDiv", "testDiv->childNodes->length"],
        ["testDiv", "testDiv->lastChild", "testDiv", "testDiv->childNodes->length - 2", "testDiv", "testDiv->childNodes->length - 1"],
        ["testDiv", "testDiv->lastChild", "testDiv", "testDiv->childNodes->length - 1", "testDiv", "testDiv->childNodes->length"],
        ["testDiv", "testDiv->lastChild", "testDiv", "testDiv->childNodes->length - 1", "testDiv", "testDiv->childNodes->length - 1"],
        ["testDiv", "testDiv->lastChild", "testDiv", "testDiv->childNodes->length", "testDiv", "testDiv->childNodes->length"],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv->lastChild", 0, "detachedDiv->lastChild", 0],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv->lastChild", 0, "detachedDiv->lastChild", 1],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv->lastChild", 1, "detachedDiv->lastChild", 1],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv", "detachedDiv->childNodes->length - 2", "detachedDiv", "detachedDiv->childNodes->length"],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv", "detachedDiv->childNodes->length - 2", "detachedDiv", "detachedDiv->childNodes->length - 1"],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv", "detachedDiv->childNodes->length - 1", "detachedDiv", "detachedDiv->childNodes->length"],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv", "detachedDiv->childNodes->length - 1", "detachedDiv", "detachedDiv->childNodes->length - 1"],
        ["detachedDiv", "detachedDiv->lastChild", "detachedDiv", "detachedDiv->childNodes->length", "detachedDiv", "detachedDiv->childNodes->length"],

        // Stuff that actually moves something
        ["paras[0]", "paras[1]", "paras[0]", 0, "paras[0]", 0],
        ["paras[0]", "paras[1]", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "paras[1]", "paras[0]", 1, "paras[0]", 1],
        ["paras[0]", "paras[1]", "testDiv", 0, "testDiv", 1],
        ["paras[0]", "paras[1]", "testDiv", 0, "testDiv", 2],
        ["paras[0]", "paras[1]", "testDiv", 1, "testDiv", 1],
        ["paras[0]", "paras[1]", "testDiv", 1, "testDiv", 2],
        ["foreignDoc", "detachedComment", "foreignDoc", "foreignDoc->childNodes->length - 1", "foreignDoc", "foreignDoc->childNodes->length"],
        ["foreignDoc", "detachedComment", "foreignDoc", "foreignDoc->childNodes->length - 1", "foreignDoc", "foreignDoc->childNodes->length - 1"],
        ["foreignDoc", "detachedComment", "foreignDoc", "foreignDoc->childNodes->length", "foreignDoc", "foreignDoc->childNodes->length"],
        ["foreignDoc", "detachedComment", "detachedComment", 0, "detachedComment", 5],
        ["paras[0]", "xmlTextNode", "paras[0]", 0, "paras[0]", 0],
        ["paras[0]", "xmlTextNode", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "xmlTextNode", "paras[0]", 1, "paras[0]", 1],

        // Stuff that throws exceptions
        ["paras[0]", "paras[0]", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "testDiv", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "document", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "foreignDoc", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "document->doctype", "paras[0]", 0, "paras[0]", 1],
    ];

    public function rangeProvider(): array
    {
        return $this->doTests(self::APPEND_CHILD_TESTS, static function ($params) {
            return $params[0] . ".appendChild(" . $params[1] . ")";
        }, [$this, '_testAppendChild']);
    }

    public function _testAppendChild($newParent, $affectedNode, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        $expectedStart = [$startContainer, $startOffset];
        $expectedEnd = [$endContainer, $endOffset];

        $expectedStart = $this->modifyForRemove($affectedNode, $expectedStart);
        $expectedEnd = $this->modifyForRemove($affectedNode, $expectedEnd);

        try {
            $newParent->appendChild($affectedNode);
        } catch (DOMException $e) {
            return [$startContainer, $startOffset, $endContainer, $endOffset];
        }

        // These two lines will actually never do anything, if you think about it,
        // but let's leave them in so correctness is more obvious.
        $expectedStart = $this->modifyForInsert($affectedNode, $expectedStart);
        $expectedEnd = $this->modifyForInsert($affectedNode, $expectedEnd);

        return array_merge($expectedStart, $expectedEnd);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
