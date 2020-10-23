<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/innerhtml-07.html
 */
class InnerHTML07Test extends TestCase
{
    public function testInnerHTMLAndStringConversionNull(): void
    {
        $document = new HTMLDocument();
        $p = $document->createElement('p');
        $p->innerHTML = null;
        self::assertSame('', $p->innerHTML);
        self::assertSame('', $p->textContent);
    }

    public function testInnerHTMLAndStringConversionNumber(): void
    {
        $document = new HTMLDocument();
        $p = $document->createElement('p');
        $p->innerHTML = 42;
        self::assertSame('42', $p->innerHTML);
        self::assertSame('42', $p->textContent);
    }

    public function testInnerHTMLAndStringConversionToString(): void
    {
        $document = new HTMLDocument();
        $p = $document->createElement('p');
        $p->innerHTML = new class () {
            public function __toString(): string
            {
                return 'pass';
            }
        };
        self::assertSame('pass', $p->innerHTML);
        self::assertSame('pass', $p->textContent);
    }
}
