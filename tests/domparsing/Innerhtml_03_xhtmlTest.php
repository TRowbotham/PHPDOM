<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\Document;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/innerhtml-03.xhtml
 */
class Innerhtml_03_xhtmlTest extends TestCase
{
    public function test1(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createElement('xmp'))
            ->appendChild($document->createElement('span'))
            ->appendChild($document->createTextNode('<'));

        self::assertSame('<xmp xmlns="http://www.w3.org/1999/xhtml"><span>&lt;</span></xmp>', $el->innerHTML);
    }

    public function test2(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('xmp');
        $el->appendChild($document->createElement('span'))
            ->appendChild($document->createTextNode('<'));

        self::assertSame('<span xmlns="http://www.w3.org/1999/xhtml">&lt;</span>', $el->innerHTML);
    }

    public function test3(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('xmp');
        $el->appendChild($document->createTextNode('<'));

        self::assertSame('&lt;', $el->innerHTML);
    }

    public function test4(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createElement('br'));

        self::assertSame('<br xmlns="http://www.w3.org/1999/xhtml" />', $el->innerHTML);
    }

    public function test5(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createElementNS("http://www.w3.org/1999/xhtml", "html:br"));

        self::assertSame('<html:br xmlns:html="http://www.w3.org/1999/xhtml" />', $el->innerHTML);
    }

    public function test6(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createTextNode("<>\"'&"));

        self::assertSame("&lt;&gt;\"'&amp;", $el->innerHTML);
    }

    public function test7(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createTextNode('&lt;&gt;&quot;&apos;&amp;'));

        self::assertSame('&amp;lt;&amp;gt;&amp;quot;&amp;apos;&amp;amp;', $el->innerHTML);
    }

    public function test8(): void
    {
        $document = $this->getDocument();
        $el = $document->createElement('div');
        $el->appendChild($document->createTextNode("à×•…\u{00A0}"));

        self::assertSame("à×•…\u{00A0}", $el->innerHTML);
    }

    public function getDocument(): Document
    {
        $document = new Document();
        $document->setContentType('application/xhtml+xml');

        return $document;
    }
}
