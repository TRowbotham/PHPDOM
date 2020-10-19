<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\tabular_data\the_caption_element;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/tabular-data/the-caption-element/caption_001.html
 */
class Caption001Test extends TestCase
{
    use WindowTrait;

    public function testFirstCaptionElementChildOfTheFirstTableElement(): void
    {
        self::assertSame(
            'first caption',
            self::getWindow()->document->getElementById('table1')->caption->innerHTML
        );
    }

    public function testSettingCaptionOnATable(): void
    {
        $document = self::getWindow()->document;
        $caption = $document->createElement('caption');
        $caption->innerHTML = 'new caption';
        $table = $document->getElementById('table1');
        $table->caption = $caption;

        self::assertSame($table, $caption->parentNode);
        self::assertSame($caption, $table->firstChild);
        self::assertSame('new caption', $table->caption->innerHTML);

        $captions = $table->getElementsByTagName('caption');
        self::assertSame(2, $captions->length);
        self::assertSame('new caption', $captions[0]->innerHTML);
        self::assertSame('second caption', $captions[1]->innerHTML);
    }

    public function testCaptionIDLAttributeIsNull(): void
    {
        self::assertNull(self::getWindow()->document->getElementById('table2')->caption);
    }

    public function testCaptionOfTheThirdElementShouldBeNull(): void
    {
        $document = self::getWindow()->document;
        $table = $document->getElementById('table3');
        $caption = $document->createElement('caption');
        $table->rows[0]->appendChild($caption);
        self::assertNull($table->caption);
    }

    public function testDynamicallyRemovingCaptionOnATable(): void
    {
        $document = self::getWindow()->document;
        self::assertNotNull($document->getElementById('table4')->caption);
        $parent = $document->getElementById('table4')->caption->parentNode;
        $parent->removeChild($document->getElementById('table4')->caption);
        self::assertNull($document->getElementById('table4')->caption);
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__;
    }

    public static function getDocumentName(): string
    {
        return 'caption_001.html';
    }
}
