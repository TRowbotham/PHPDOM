<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Exception\DOMException;

use function array_merge;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-mutations-replaceChild.html
 */
class Range_mutations_replaceChildTest extends RangeTestCase
{
    use Range_mutationTrait;

    private const REPLACE_CHILD_TESTS = [
        // Moving a node to its current position.  Doesn't match most browsers'
        // behavior, but we probably want to keep the spec the same anyway:
        // https://bugzilla.mozilla.org/show_bug.cgi?id=647603
        ["testDiv", "paras[0]", "paras[0]", "paras[0]", 0, "paras[0]", 0],
        ["testDiv", "paras[0]", "paras[0]", "paras[0]", 0, "paras[0]", 1],
        ["testDiv", "paras[0]", "paras[0]", "paras[0]", 1, "paras[0]", 1],
        ["testDiv", "paras[0]", "paras[0]", "testDiv", 0, "testDiv", 2],
        ["testDiv", "paras[0]", "paras[0]", "testDiv", 1, "testDiv", 1],
        ["testDiv", "paras[0]", "paras[0]", "testDiv", 1, "testDiv", 2],
        ["testDiv", "paras[0]", "paras[0]", "testDiv", 2, "testDiv", 2],

        // Stuff that actually moves something.
        ["paras[0]", "paras[1]", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 0],
        ["paras[0]", "paras[1]", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "paras[1]", "paras[0]->firstChild", "paras[0]", 1, "paras[0]", 1],
        ["paras[0]", "paras[1]", "paras[0]->firstChild", "testDiv", 0, "testDiv", 1],
        ["paras[0]", "paras[1]", "paras[0]->firstChild", "testDiv", 0, "testDiv", 2],
        ["paras[0]", "paras[1]", "paras[0]->firstChild", "testDiv", 1, "testDiv", 1],
        ["paras[0]", "paras[1]", "paras[0]->firstChild", "testDiv", 1, "testDiv", 2],
        ["foreignDoc", "detachedComment", "foreignDoc->documentElement", "foreignDoc", 0, "foreignDoc", 0],
        ["foreignDoc", "detachedComment", "foreignDoc->documentElement", "foreignDoc", 0, "foreignDoc", 1],
        ["foreignDoc", "detachedComment", "foreignDoc->documentElement", "foreignDoc", 0, "foreignDoc", 2],
        ["foreignDoc", "detachedComment", "foreignDoc->documentElement", "foreignDoc", 1, "foreignDoc", 1],
        ["foreignDoc", "detachedComment", "foreignDoc->doctype", "foreignDoc", 0, "foreignDoc", 0],
        ["foreignDoc", "detachedComment", "foreignDoc->doctype", "foreignDoc", 0, "foreignDoc", 1],
        ["foreignDoc", "detachedComment", "foreignDoc->doctype", "foreignDoc", 0, "foreignDoc", 2],
        ["foreignDoc", "detachedComment", "foreignDoc->doctype", "foreignDoc", 1, "foreignDoc", 1],
        ["paras[0]", "xmlTextNode", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 0],
        ["paras[0]", "xmlTextNode", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "xmlTextNode", "paras[0]->firstChild", "paras[0]", 1, "paras[0]", 1],

        // Stuff that throws exceptions
        ["paras[0]", "paras[0]", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "testDiv", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "document", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "foreignDoc", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 1],
        ["paras[0]", "document->doctype", "paras[0]->firstChild", "paras[0]", 0, "paras[0]", 1],
    ];

    public function rangeProvider(): array
    {
        return $this->doTests(self::REPLACE_CHILD_TESTS, static function ($params) {
            return $params[0] . ".replaceChild(" . $params[1] . ", " . $params[2] . ")";
        }, [$this, '_testReplaceChild']);
    }

    public function _testReplaceChild($newParent, $newChild, $oldChild, $startContainer, $startOffset, $endContainer, $endOffset)
    {
        $expectedStart = [$startContainer, $startOffset];
        $expectedEnd = [$endContainer, $endOffset];

        $expectedStart = $this->modifyForRemove($oldChild, $expectedStart);
        $expectedEnd = $this->modifyForRemove($oldChild, $expectedEnd);

        if ($newChild !== $oldChild) {
            // Don't do this twice, if they're the same!
            $expectedStart = $this->modifyForRemove($newChild, $expectedStart);
            $expectedEnd = $this->modifyForRemove($newChild, $expectedEnd);
        }

        try {
            $newParent->replaceChild($newChild, $oldChild);
        } catch (DOMException $e) {
            return [$startContainer, $startOffset, $endContainer, $endOffset];
        }

        $expectedStart = $this->modifyForInsert($newChild, $expectedStart);
        $expectedEnd = $this->modifyForInsert($newChild, $expectedEnd);

        return array_merge($expectedStart, $expectedEnd);
    }

    public static function getDocumentName(): string
    {
        return 'Range-mutations-appendChild.html';
    }
}
