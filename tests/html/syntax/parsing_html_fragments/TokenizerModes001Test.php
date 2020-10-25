<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\parsing_html_fragments;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/parsing-html-fragments/tokenizer-modes-001.html
 */
class TokenizerModes001Test extends TestCase
{
    public function testTitleShouldNotBreakOutOfTitle(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('title');
        $e->innerHTML = '</title><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('&lt;/title&gt;&lt;div&gt;', $e->innerHTML);
    }

    public function testTextareaShouldNotBreakOutOfTextarea(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('textarea');
        $e->innerHTML = '</textarea><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('&lt;/textarea&gt;&lt;div&gt;', $e->innerHTML);
    }

    public function testStyleShouldNotBreakOutOfStyle(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('style');
        $e->innerHTML = '</style><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('</style><div>', $e->innerHTML);
    }

    public function testXmpShouldNotBreakOutOfXmp(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('xmp');
        $e->innerHTML = '</xmp><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('</xmp><div>', $e->innerHTML);
    }

    public function testIframeShouldNotBreakOutOfIframe(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('iframe');
        $e->innerHTML = '</iframe><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('</iframe><div>', $e->innerHTML);
    }

    public function testNoembedShouldNotBreakOutOfNoembed(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('noembed');
        $e->innerHTML = '</noembed><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('</noembed><div>', $e->innerHTML);
    }

    public function testNoframesShouldNotBreakOutOfNoframes(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('noframes');
        $e->innerHTML = '</noframes><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('</noframes><div>', $e->innerHTML);
    }

    public function testScriptShouldNotBreakOutOfScript(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('script');
        $e->innerHTML = '<\/script><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('<\/script><div>', $e->innerHTML);
    }

    public function testNoscriptShouldNotBreakOutOfNoscript(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('noscript');
        $e->innerHTML = '</noscript><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('</noscript><div>', $e->innerHTML);
    }

    public function testPlaintextShouldNotBreakOutOfPlaintext(): void
    {
        $document = new HTMLDocument();
        $e = $document->createElement('plaintext');
        $e->innerHTML = '</plaintext><div>';
        self::assertSame(0, $e->getElementsByTagName('div')->length);
        self::assertSame('</plaintext><div>', $e->innerHTML);
    }
}
