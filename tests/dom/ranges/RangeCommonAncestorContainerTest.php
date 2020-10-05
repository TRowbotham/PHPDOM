<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

use function array_unshift;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-commonAncestorContainer.html
 */
class RangeCommonAncestorContainerTest extends TestCase
{
    use Common;

    public function rangeProvider(): array
    {
        global $document, $testRanges;

        $document = $this->getDocument();
        self::setupRangeTests($document);
        array_unshift($testRanges, '[detached]');

        foreach ($testRanges as $i => $range) {
            $tests[] = [$i, $range, $document];
        }

        return $tests;
    }

    /**
     * @dataProvider rangeProvider
     */
    public function testRanges(int $i, string $endpoints): void
    {
        global $document;

        if ($i === 0) {
            $range = $document->createRange();
            $range->detach();
        } else {
            $range = $this->rangeFromEndpoints($this->eval($endpoints, $document));
        }

        // "Let container be start node."
        $container = $range->startContainer;

        // "While container is not an inclusive ancestor of end node, let
        // container be container's parent."
        while ($container !== $range->endContainer && !$this->isAncestor($container, $range->endContainer)) {
            $container = $container->parentNode;
        }

        $this->assertSame($container, $range->commonAncestorContainer);
    }

    public function getDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.commonAncestorContainer tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

testRanges.unshift("[detached]");

for (var i = 0; i < testRanges.length; i++) {
    test(function() {
    var range;
    if (i == 0) {
        range = document.createRange();
        range.detach();
    } else {
        range = rangeFromEndpoints(eval(testRanges[i]));
    }

    // "Let container be start node."
    var container = range.startContainer;

    // "While container is not an inclusive ancestor of end node, let
    // container be container's parent."
    while (container != range.endContainer
    && !isAncestor(container, range.endContainer)) {
        container = container.parentNode;
    }

    // "Return container."
    assert_equals(range.commonAncestorContainer, container);
    }, i + ": range " + testRanges[i]);
}

testDiv.style.display = "none";
</script>
TEST_HTML;

        $p = new DOMParser();

        return $p->parseFromString($html, 'text/html');
    }
}
