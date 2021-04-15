<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\semantics\text_level_semantics\the_a_element;

use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/semantics/text-level-semantics/the-a-element/a.text-getter-01.html
 */
class TextGetterTest extends TestCase
{
    use WindowTrait;

    public function testTextGetter(): void
    {
        $document = self::getWindow()->document;

        $e = $document->getElementById('test')->appendChild($document->createElement('a'));
        $e->href = 'd';
        $e->appendChild($document->createTextNode('a '));
        $e->appendChild($document->createTextNode('b '));
        $e->appendChild($document->createTextNode('c '));

        $list = $document->getElementById('test')->getElementsByTagName('a');

        foreach ($list as $a) {
            self::assertSame($a->textContent, $a->text);
            self::assertSame('a b c ', $a->text);
        }
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }

    public static function getDocumentName(): string
    {
        return 'a.text-getter-01.html';
    }
}
