<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\serializing_html_fragments;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/serializing-html-fragments/escaping.html
 */
class EscapingTest extends TestCase
{
    private const HTML_ESCAPED = '&amp;&nbsp;&lt;&gt;';
    private const HTML_UNESCAPED = "&\u{00A0}<>";
    private const DOC_STRING = <<<'DOCUMENT'
<!DOCTYPE html>
<meta charset="utf-8">
<title>Serialization of script-disabled documents should follow escaping rules</title>
<link rel="author" href="mailto:masonf@chromium.org">
<link rel="help" href="https://html.spec.whatwg.org/multipage/parsing.html#serialising-html-fragments">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>

<body>
DOCUMENT;

    public function testDivInnerHTML(): void
    {
        $document = DocumentBuilder::create()->emulateScripting(true)->setContentType('text/html')->createFromString(self::DOC_STRING);
        $div = $document->createElement('div');
        $document->body->appendChild($div);
        $div->innerHTML = $this->getHtml(false);
        $this->checkDoc(false, $div);
    }

    public function testDivInsertAdjacentHTML(): void
    {
        $document = DocumentBuilder::create()->emulateScripting(true)->setContentType('text/html')->createFromString(self::DOC_STRING);
        $div = $document->createElement('div');
        $div->insertAdjacentHTML('afterbegin', $this->getHtml(false));
        $this->checkDoc(false, $div);
    }

    public function testDocumentWriteOnMainDocument(): void
    {
        $this->markTestSkipped('We don\'t support Document.write().');

        $document = (new HTMLDocument())->implementation->createHTMLDocument();
        $id = 'doc-write-1';
        $document->write("<div id={$id} style=\"display:none\">{$this->getHtml(false)}</div>");
        $this->checkDoc(false, $document->getElementById($id));
    }

    public function testDOMParserParseFromString(): void
    {
        $doc = (new DOMParser())->parseFromString("<body>{$this->getHtml(true)}</body>", 'text/html');
        $this->checkDoc(true, $doc->body);
    }

    public function testTemplateContent(): void
    {
        $document = (new HTMLDocument())->implementation->createHTMLDocument();
        $template = $document->createElement('template');
        $document->body->appendChild($template);
        $template->innerHTML = $this->getHtml(true);
        $this->checkDoc(true, $template->content);
    }

    public function testDocumentCreateHTMLDocumentAndInnerHTML(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('');
        $doc->body->innerHTML = "<pre>{$this->getHtml(true)}</pre>";
        $this->checkDoc(true, $doc->body->firstChild);
    }

    public function testDocumentCreateHTMLDocumentAndRangeCreateContextualFragment(): void
    {
        $doc = (new HTMLDocument())->implementation->createHTMLDocument('');
        $range = $doc->createRange();
        $range->selectNode($doc->body);
        $frag = $range->createContextualFragment($this->getHtml(true));
        $this->checkDoc(true, $frag);
    }

    public function testDocumentCreateHTMLDocumentAndDocumentWrite(): void
    {
        $this->markTestSkipped('We don\'t support Document.write().');

        $document = (new HTMLDocument())->implementation->createHTMLDocument();
        $id = 'doc-write-2';
        $document->write("<div id={$id} style=\"display:none\">{$this->getHtml(false)}</div>");
        $this->checkDoc(true, $document->getElementById($id));
    }

    private function getHtml(bool $deEscapeParse): string
    {
        return '<noscript>' . ($deEscapeParse ? self::HTML_ESCAPED : self::HTML_UNESCAPED) . '</noscript>';
    }

    private function checkDoc(bool $escapeSerialize, Node $parsedNode): void
    {
        $node = $parsedNode->firstChild;
        $innerText = $node->textContent;
        self::assertSame(self::HTML_UNESCAPED, $innerText);
        $serialized = $node->innerHTML;
        $expectation = $escapeSerialize ? self::HTML_ESCAPED : self::HTML_UNESCAPED;
        self::assertSame($expectation, $serialized);
    }
}
