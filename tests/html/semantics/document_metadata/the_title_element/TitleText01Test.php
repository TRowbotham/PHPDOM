<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\document_metadata\the_title_element;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/document-metadata/the-title-element/title.text-01.html
 */
class TitleText01Test extends TestCase
{
    use WindowTrait;

    public function testTitleText(): void
    {
        $document = self::getWindow()->document;
        $title = $document->getElementsByTagName('title')[0];

        while ($title->childNodes->length) {
            $title->removeChild($title->childNodes[0]);
        }

        $title->appendChild($document->createComment('COMMENT'));
        $title->appendChild($document->createTextNode('TEXT'));
        $title->appendChild($document->createElement('a'))
            ->appendChild($document->createTextNode('ELEMENT'));

        self::assertSame('TEXT', $title->text);
        self::assertSame('TEXTELEMENT', $title->textContent);
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'title.text-01.html';
    }
}
