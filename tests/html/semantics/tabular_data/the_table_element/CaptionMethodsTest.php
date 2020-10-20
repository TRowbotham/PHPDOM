<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_table_element;

use Rowbot\DOM\Element\HTML\HTMLTableCaptionElement;
use Rowbot\DOM\Exception\HierarchyRequestError;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-table-element/caption-methods.html
 */
class CaptionMethodsTest extends TableTestCase
{
    use WindowTrait;

    public function testMethodReturnsNewCaptionIfExistingCaptionIsNotInHtmlNamespace(): void
    {
        $document = self::getWindow()->document;
        $table0 = $document->getElementById('table0');
        $caption = $document->createElementNS('foo', 'caption');
        $table0->appendChild($caption);
        $table0FirstNode = $table0->firstChild;
        $testCaption = $table0->createCaption();
        self::assertNotSame($table0FirstNode, $testCaption);
        self::assertSame($testCaption, $table0->firstChild);
    }

    public function testMethodReturnsTheFirstCaptionElementChildOfTheTable(): void
    {
        $document = self::getWindow()->document;
        $table1 = $document->getElementById('table1');
        $testCaption = $table1->createCaption();
        $table1FirstCaption = $table1->caption;
        self::assertSame($testCaption, $table1FirstCaption);
    }

    public function testMethodCreatesANewCaptionAndInsertsItAsTheFirstNodeOfTheTableElement(): void
    {
        $document = self::getWindow()->document;
        $table2 = $document->getElementById('table2');
        $test2Caption = $table2->createCaption();
        $table2FirstNode = $table2->firstChild;
        self::assertInstanceOf(HTMLTableCaptionElement::class, $test2Caption);
        self::assertSame($test2Caption, $table2FirstNode);
    }

    public function testCreateCaptionWillNotCreateNewCaptionIfOneExists(): void
    {
        $document = self::getWindow()->document;
        $table = $document->createElement('table');
        self::assertSame($table->createCaption(), $table->createCaption());
    }

    public function testCreateCaptionWillNotCopyTablesPrefix(): void
    {
        $document = self::getWindow()->document;
        $table = $document->createElementNS("http://www.w3.org/1999/xhtml", "foo:table");
        $caption = $table->createCaption();
        self::assertNull($caption->prefix);
    }

    public function testDeleteCaptionMethodRemovesTheFirstCaptionElementChildOfTheTableElement(): void
    {
        $document = self::getWindow()->document;
        $table3 = $document->getElementById('table3');
        self::assertSame('caption 3', $table3->caption->textContent);
        $table3->deleteCaption();
        self::assertNull($table3->caption);
    }

    public function testDeleteCaptionMethodDoesNotRemoveCaptionThatIsNotInHtmlNamespace(): void
    {
        $document = self::getWindow()->document;
        $table4 = $document->getElementById('table4');
        $caption = $document->createElementNS('foo', 'caption');
        $table4->appendChild($caption);
        $table4->deleteCaption();
        self::assertSame($table4, $caption->parentNode);
    }

    public function testSettingCaptionRethrowsException(): void
    {
        $document = self::getWindow()->document;
        $table5 = $document->getElementById('table5');
        $caption = $document->createElement('caption');
        $caption->appendChild($table5);

        $this->assertThrows(static function () use ($table5, $caption): void {
            $table5->caption = $caption;
        }, HierarchyRequestError::class);

        self::assertNotSame($caption, $table5->caption);
    }

    public function testAssigningACaptionToTableCaption(): void
    {
        $document = self::getWindow()->document;
        $table6 = $document->getElementById('table6');
        $caption = $document->getElementById('caption6');
        self::assertSame($caption, $table6->caption);

        $newCaption = $document->createElement('caption');
        $table6->caption = $newCaption;
        self::assertSame($table6, $newCaption->parentNode);
        self::assertSame($newCaption, $table6->firstChild);
        self::assertSame($newCaption, $table6->caption);
    }

    public function testAssigningNullToTableCaption(): void
    {
        $document = self::getWindow()->document;
        $table7 = $document->getElementById('table7');
        $caption = $document->getElementById('caption7');
        self::assertSame($caption, $table7->caption);

        $table7->caption = null;
        self::assertNull($caption->parentNode);
        self::assertSame($document->getElementById('tbody7'), $table7->firstElementChild);
        self::assertNull($table7->caption);
    }

    public function testAssigningANonCaptionToTableCaption(): void
    {
        $document = self::getWindow()->document;
        $table8 = $document->createElement('table');
        $caption = $document->createElement("captİon");
        $this->expectException(TypeError::class);
        $table8->caption = $caption;
    }

    public function testAssigningAForeignCaptionToTableCaption(): void
    {
        $document = self::getWindow()->document;
        $table9 = $document->createElement('table');
        $caption = $document->createElementNS("http://www.example.com", "caption");
        $this->expectException(TypeError::class);
        $table9->caption = $caption;
    }

    public function testSetTableCaptionWhenTableDoesntAlreadyHaveACaption(): void
    {
        $document = self::getWindow()->document;
        $table = $document->createElement('table');
        $caption = $document->createElement('caption');
        $caption->innerHTML = 'new caption';
        $table->caption = $caption;

        self::assertSame($table, $caption->parentNode);
        self::assertSame($caption, $table->firstChild);
        self::assertSame('new caption', $table->caption->innerHTML);
    }

    public function testSetTableCaptionWhenTheTableHasACaptionChildButWithOtherSiblingsBeforeIt(): void
    {
        $document = self::getWindow()->document;
        $table10 = $document->getElementById('table10');
        $caption = $document->createElement('caption');
        $caption->innerHTML = 'new caption';
        $table10->caption = $caption;

        self::assertSame($table10, $caption->parentNode);
        self::assertSame($caption, $table10->firstChild);
        self::assertSame('new caption', $table10->caption->innerHTML);

        $captions = $table10->getElementsByTagName('caption');
        self::assertSame(1, $captions->length);
    }

    public function testSetTableCaptionWhenTheTableHasACaptionDescendant(): void
    {
        $document = self::getWindow()->document;
        $table11 = $document->getElementById('table11');
        $caption = $document->createElement('caption');
        $caption->innerHTML = 'new caption';
        $table11->caption = $caption;

        self::assertSame($table11, $caption->parentNode);
        self::assertSame($caption, $table11->firstChild);
        self::assertSame('new caption', $table11->caption->innerHTML);

        $captions = $table11->getElementsByTagName('caption');
        self::assertSame(1, $captions->length);
    }

    public function testSetTableCaptionWhenTheTableHasTwoCaptionChildren(): void
    {
        $document = self::getWindow()->document;
        $table12 = $document->getElementById('table12');
        $caption = $document->createElement('caption');
        $caption->innerHTML = 'new caption';
        $table12->caption = $caption;

        self::assertSame($table12, $caption->parentNode);
        self::assertSame($caption, $table12->firstChild);
        self::assertSame('new caption', $table12->caption->innerHTML);

        $captions = $table12->getElementsByTagName('caption');
        self::assertSame(2, $captions->length);
        self::assertSame('new caption', $captions[0]->innerHTML);
        self::assertSame('caption 2', $captions[1]->innerHTML);
    }

    public function testAssigningACaptionHasADifferentOwnerDocumentToTableCaption(): void
    {
        self::markTestSkipped('We don\'t support iframes yet');

        $document = self::getWindow()->document;
        $table13 = $document->getElementById('table13');
        $iframe = $document->createElement('iframe');
        $iframe->srcdoc = '<table><caption id="caption13">caption 13</caption></table>';
        $document->body->appendChild($iframe);

        $iframe->addEventListener('load', static function () use ($table13, $iframe): void {
            $caption = $iframe->contentWindow->document->getElementById('caption13');

            self::assertSame($table13, $caption->parentNode);
            self::assertSame($caption, $table13->firstChild);
            self::assertSame('caption 13', $table13->caption->innerHTML);

            $captions = $table13->getElementsByTagName('caption');
            self::assertSame(1, $captions->length);
        });
    }

    public function testAssigningTheCaptionAlreadyInTheTableToTableCaption(): void
    {
        $document = self::getWindow()->document;
        $table14 = $document->getElementById('table14');
        $caption = $document->getElementById('caption14');
        $table14->caption = $caption;

        self::assertSame($table14, $caption->parentNode);
        self::assertSame($caption, $table14->firstChild);

        $captions = $table14->getElementsByTagName('caption');
        self::assertSame(1, $captions->length);
    }

    public static function getDocumentName(): string
    {
        return 'caption-methods.html';
    }
}
