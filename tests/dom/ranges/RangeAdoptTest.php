<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-adopt-test.html
 */
class RangeAdoptTest extends TestCase
{
    protected static $document;

    public function createRangeWithUnparentedContainerOfSingleElement(): Range
    {
        $document = self::$document;
        $range = $document->createRange();
        $container = $document->createElement('container');
        $element = $document->createElement('element');
        $container->appendChild($element);
        $range->selectNode($element);

        return $range;
    }

    public function nestRangeInOuterContainer(Range $range): void
    {
        $range->startContainer->ownerDocument->createElement('outer')->appendChild($range->startContainer);
    }

    public function moveNodeToNewlyCreatedDocumentWithAppendChild(Node $node): void
    {
        self::$document->implementation->createDocument(null, null)->appendChild($node);
    }

    public function testRangInDocumentRemovingTheOnlyElementInTheRangeMuistCollapseTheRange(): void
    {
        $range = $this->createRangeWithUnparentedContainerOfSingleElement();
        $range->startContainer->removeChild($range->startContainer->firstChild);

        $this->assertSame(0, $range->endOffset);
    }

    public function testParentedRangeContainerMovedToAnotherDocumentWithAppendChild(): void
    {
        $range = $this->createRangeWithUnparentedContainerOfSingleElement();
        $this->nestRangeInOuterContainer($range);
        $this->moveNodeToNewlyCreatedDocumentWithAppendChild($range->startContainer);

        $this->assertSame(0, $range->endOffset);
    }

    public function testParentedRangeContainerMovedToAnotherDocumentWithAppendChild2(): void
    {
        $range = $this->createRangeWithUnparentedContainerOfSingleElement();
        $this->moveNodeToNewlyCreatedDocumentWithAppendChild($range->startContainer);
        $range->startContainer->removeChild($range->startContainer->firstChild);

        $this->assertSame(0, $range->endOffset);
    }

    public function testRangeContainersParentlessContainerMovedToAnotherDocumentWithAppendChild(): void
    {
        $range = $this->createRangeWithUnparentedContainerOfSingleElement();
        $this->nestRangeInOuterContainer($range);
        $this->moveNodeToNewlyCreatedDocumentWithAppendChild($range->startContainer->parentNode);
        $range->startContainer->removeChild($range->startContainer->firstChild);

        $this->assertSame(0, $range->endOffset);
    }

    public static function setUpBeforeClass(): void
    {
        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<script src='/resources/testharness.js'></script>
<script src='/resources/testharnessreport.js'></script>
<script>
function createRangeWithUnparentedContainerOfSingleElement() {
    const range = document.createRange();
    const container = document.createElement("container");
    const element = document.createElement("element");
    container.appendChild(element);
    range.selectNode(element);
    return range;
}
function nestRangeInOuterContainer(range) {
    range.startContainer.ownerDocument.createElement("outer").appendChild(range.startContainer);
}
function moveNodeToNewlyCreatedDocumentWithAppendChild(node) {
    document.implementation.createDocument(null, null).appendChild(node);
}

//Tests removing only element
test(()=>{
    const range = createRangeWithUnparentedContainerOfSingleElement();
    range.startContainer.removeChild(range.startContainer.firstChild);
    assert_equals(range.endOffset, 0);
}, "Range in document: Removing the only element in the range must collapse the range");


//Tests removing only element after parented container moved to another document
test(()=>{
    const range = createRangeWithUnparentedContainerOfSingleElement();
    nestRangeInOuterContainer(range);
    moveNodeToNewlyCreatedDocumentWithAppendChild(range.startContainer);
    assert_equals(range.endOffset, 0);
}, "Parented range container moved to another document with appendChild: Moving the element to the other document must collapse the range");

//Tests removing only element after parentless container moved oo another document
test(()=>{
    const range = createRangeWithUnparentedContainerOfSingleElement();
    moveNodeToNewlyCreatedDocumentWithAppendChild(range.startContainer);
    range.startContainer.removeChild(range.startContainer.firstChild);
    assert_equals(range.endOffset, 0);
}, "Parentless range container moved to another document with appendChild: Removing the only element in the range must collapse the range");

//Tests removing only element after parentless container of container moved to another document
test(()=>{
    const range = createRangeWithUnparentedContainerOfSingleElement();
    nestRangeInOuterContainer(range);
    moveNodeToNewlyCreatedDocumentWithAppendChild(range.startContainer.parentNode);
    range.startContainer.removeChild(range.startContainer.firstChild);
    assert_equals(range.endOffset, 0);
}, "Range container's parentless container moved to another document with appendChild: Removing the only element in the range must collapse the range");
</script>
TEST_HTML;

        $p = new DOMParser();
        self::$document = $p->parseFromString($html, 'text/html');
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
