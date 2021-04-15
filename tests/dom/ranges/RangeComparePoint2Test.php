<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-comparePoint-2.html
 */
class RangeComparePoint2Test extends TestCase
{
    public function testNodeInSameDocument(): void
    {
        $document = $this->getDocument();
        $r = $document->createRange();
        $r->detach();
        $this->assertSame(1, $r->comparePoint($document->body, 0));
    }

    public function testCompareNodesInDifferentDocuments(): void
    {
        $document = $this->getDocument();
        $doc = $document->implementation->createHTMLDocument('tralala');
        $r = $document->createRange();
        $this->expectException(WrongDocumentError::class);
        $r->comparePoint($doc->body, 0);
    }

    public function getDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<title>Range.comparePoint</title>
<link rel="author" title="Ms2ger" href="mailto:ms2ger@gmail.com">
<meta name=timeout content=long>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<div id=log></div>
<script>
test(function() {
    var r = document.createRange();
    r.detach()
    assert_equals(r.comparePoint(document.body, 0), 1)
})
test(function() {
    var r = document.createRange();
    assert_throws_js(TypeError, function() { r.comparePoint(null, 0) })
})
test(function() {
    var doc = document.implementation.createHTMLDocument("tralala")
    var r = document.createRange();
    assert_throws_dom("WRONG_DOCUMENT_ERR", function() { r.comparePoint(doc.body, 0) })
})
</script>
TEST_HTML;

        $p = new DOMParser();

        return $p->parseFromString($html, 'text/html');
    }
}
