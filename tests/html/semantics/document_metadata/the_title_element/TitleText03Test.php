<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\document_metadata\the_title_element;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/document-metadata/the-title-element/title.text-03.html
 */
class TitleText03Test extends TestCase
{
    use WindowTrait;

    /**
     * @dataProvider titleProvider
     */
    public function testTitleText(string $str): void
    {
        $document = self::getWindow()->document;
        $document->title = $str;
        $title = $document->getElementsByTagName('title')[0];
        self::assertSame($str, $title->text);
        self::assertSame($str, $title->textContent);
        self::assertSame($str, $title->firstChild->nodeValue);
    }

    public function titleProvider(): array
    {
        return [
            ["one space", "two  spaces"],
            ["one\ttab", "two\t\ttabs"],
            ["one\nnewline", "two\n\nnewlines"],
            ["one\fform feed", "two\f\fform feeds"],
            ["one\rcarriage return", "two\r\rcarriage returns"],
        ];
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
