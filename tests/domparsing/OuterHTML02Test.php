<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/outerhtml-02.html
 */
class OuterHTML02Test extends TestCase
{
    public function testOuterHTMLAndStringConversionNull(): void
    {
        $document = new HTMLDocument();
        $div = $document->createElement('div');
        $p = $div->appendChild($document->createElement('p'));
        $p->outerHTML = null;
        self::assertSame('', $p->innerHTML);
        self::assertSame('', $p->textContent);
    }

    public function testOuterHTMLAndStringConversionNumber(): void
    {
        $document = new HTMLDocument();
        $div = $document->createElement('div');
        $p = $div->appendChild($document->createElement('p'));
        $p->outerHTML = 42;
        self::assertSame('42', $p->innerHTML);
        self::assertSame('42', $p->textContent);
    }

    public function testOuterHTMLAndStringConversionToString(): void
    {
        $document = new HTMLDocument();
        $div = $document->createElement('div');
        $p = $div->appendChild($document->createElement('p'));
        $p->outerHTML = new class () {
            public function __toString(): string
            {
                return 'pass';
            }
        };
        self::assertSame('pass', $p->innerHTML);
        self::assertSame('pass', $p->textContent);
    }
}
