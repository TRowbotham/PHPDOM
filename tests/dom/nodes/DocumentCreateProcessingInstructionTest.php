<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;
use Rowbot\DOM\ProcessingInstruction;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createProcessingInstruction.js
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createProcessingInstruction.html
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createProcessingInstruction-xhtml.xhtml
 */
class DocumentCreateProcessingInstructionTest extends TestCase
{
    private static $htmlDocument;
    private static $xhtmlDocument;

    /**
     * @dataProvider invalidNamesProvider
     */
    public function testCreateProcessingInstructionInvalidNames(
        HTMLDocument $document,
        $target,
        string $data
    ): void {
        $this->expectException(InvalidCharacterError::class);

        $document->createProcessingInstruction((string) $target, $data);
    }

    /**
     * @dataProvider validNamesProvider
     */
    public function testCreateProcessingInstructionValidNames(
        HTMLDocument $document,
        $target,
        string $data
    ): void {
        $pi = $document->createProcessingInstruction($target, $data);

        $this->assertSame($target, $pi->target);
        $this->assertSame($data, $pi->data);
        $this->assertSame($document, $pi->ownerDocument);
        $this->assertInstanceOf(ProcessingInstruction::class, $pi);
        $this->assertInstanceOf(Node::class, $pi);
    }

    public function invalidNamesProvider(): Generator
    {
        $invalid = [
            ["A", "?>"],
            ["\u{00B7}A", "x"],
            ["\u{00D7}A", "x"],
            ["A\u{00D7}", "x"],
            ["\\A", "x"],
            ["\f", "x"],
            [0, "x"],
            ["0", "x"]
        ];
        $documents = [self::loadHTMLDocument(), self::loadXHTMLDocument()];

        foreach ($invalid as [$target, $data]) {
            foreach ($documents as $document) {
                yield [$document, $target, $data];
            }
        }
    }

    public function validNamesProvider(): Generator
    {
        $valid = [
            ["xml:fail", "x"],
            ["A\u{00B7A}", "x"],
            ["a0", "x"],
        ];

        $documents = [self::loadHTMLDocument(), self::loadXHTMLDocument()];

        foreach ($valid as [$target, $data]) {
            foreach ($documents as $document) {
                yield [$document, $target, $data];
            }
        }
    }

    public static function loadHTMLDocument(): HTMLDocument
    {
        if (self::$htmlDocument) {
            return self::$htmlDocument;
        }

        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<meta charset=utf-8>
<title>Document.createProcessingInstruction in HTML documents</title>
<link rel=help href="https://dom.spec.whatwg.org/#dom-document-createprocessinginstruction">
<link rel=help href="https://dom.spec.whatwg.org/#dom-processinginstruction-target">
<link rel=help href="https://dom.spec.whatwg.org/#dom-characterdata-data">
<link rel=help href="https://dom.spec.whatwg.org/#dom-node-ownerdocument">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<div id=log></div>
<script src="Document-createProcessingInstruction.js"></script>
TEST_HTML;

        $parser = new DOMParser();
        self::$htmlDocument = $parser->parseFromString($html, 'text/html');

        return self::$htmlDocument;
    }

    public static function loadXHTMLDocument(): HTMLDocument
    {
        if (self::$xhtmlDocument) {
            return self::$xhtmlDocument;
        }

        $html = <<<'TEST_HTML'
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Document.createProcessingInstruction in XML documents</title>
<link rel="help" href="https://dom.spec.whatwg.org/#dom-document-createprocessinginstruction"/>
<link rel="help" href="https://dom.spec.whatwg.org/#dom-processinginstruction-target"/>
<link rel="help" href="https://dom.spec.whatwg.org/#dom-characterdata-data"/>
<link rel="help" href="https://dom.spec.whatwg.org/#dom-node-ownerdocument"/>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
</head>
<body>
<div id="log"/>
<script src="Document-createProcessingInstruction.js"/>
</body>
</html>
TEST_HTML;

            $parser = new DOMParser();
            self::$xhtmlDocument = $parser->parseFromString($html, 'text/html');

            return self::$xhtmlDocument;
    }

    public static function tearDownAfterClass(): void
    {
        self::$htmlDocument = null;
        self::$xhtmlDocument = null;
    }
}
