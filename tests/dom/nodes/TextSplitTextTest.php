<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Text-splitText.html
 */
class TextSplitTextTest extends TestCase
{
    private static $document;

    public function testSplitTextAfterEndOfData(): void
    {
        $this->expectException(IndexSizeError::class);

        $text = self::loadDocument()->createTextNode('camembert');
        $text->splitText(10);
    }

    public function testSplitEmptyTest(): void
    {
        $text = self::loadDocument()->createTextNode('');
        $newText = $text->splitText(0);

        $this->assertSame('', $text->data);
        $this->assertSame('', $newText->data);
    }

    public function testSplitTextAtBeginning(): void
    {
        $text = self::loadDocument()->createTextNode('comté');
        $newText = $text->splitText(0);

        $this->assertSame('', $text->data);
        $this->assertSame('comté', $newText->data);
    }

    public function testSplitTextAtEnd(): void
    {
        $text = self::loadDocument()->createTextNode('comté');
        $newText = $text->splitText(5);

        $this->assertSame('comté', $text->data);
        $this->assertSame('', $newText->data);
    }

    public function testSplitRoot(): void
    {
        $text = self::loadDocument()->createTextNode('comté');
        $newText = $text->splitText(3);

        $this->assertSame('com', $text->data);
        $this->assertSame('té', $newText->data);
        $this->assertNull($newText->parentNode);
    }

    public function testSplitChild(): void
    {
        $document = self::loadDocument();
        $parent = $document->createElement('div');
        $text = $document->createTextNode('bleu');
        $parent->appendChild($text);
        $newText = $text->splitText(2);

        $this->assertSame('bl', $text->data);
        $this->assertSame('eu', $newText->data);
        $this->assertSame($newText, $text->nextSibling);
        $this->assertSame($parent, $newText->parentNode);
    }

    public static function loadDocument(): HTMLDocument
    {
        if (!self::$document) {
            self::$document = new HTMLDocument();
        }

        return self::$document;
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
