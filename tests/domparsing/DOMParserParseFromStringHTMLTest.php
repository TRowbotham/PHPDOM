<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Element\HTML\HTMLHtmlElement;
use Rowbot\DOM\Element\HTML\HTMLParagraphElement;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/DOMParser-parseFromString-html.html
 */
class DOMParserParseFromStringHTMLTest extends TestCase
{
    private static $doc;
    private static $parser;

    public function testParsingOfIdAttribute(): void
    {
        $root = self::$doc->documentElement;
        $this->assertNode($root, ['type' => HTMLHtmlElement::class, 'id' => 'root']);
    }

    public function testContentType(): void
    {
        $this->assertSame('text/html', self::$doc->contentType);
    }

    public function testCompatMode(): void
    {
        $this->markTestSkipped('We don\'t support Document::compatMode');
        $this->assertSame('BackCompat', self::$doc->compatMode);
    }

    public function testCompatModeWithProperDoctype(): void
    {
        $this->markTestSkipped('We don\'t support Document::compatMode');
        $input = '<!DOCTYPE html><html id="root"><head></head><body></body></html>';
        self::$doc = self::$parser->parseFromString($input, 'text/html');
        $this->assertSame('CSS1Compat', self::$doc->compatMode);
    }

    public function testLocationValue(): void
    {
        $this->markTestSkipped('We don\'t support Document::location.');
        $this->assertNull(self::$doc->location);
    }

    public function testDOMParserParsesHTMLTagSoupWithNoProblems(): void
    {
        $soup = "<!DOCTYPE foo></><foo></multiple></>";
        $htmldoc = (new DOMParser())->parseFromString($soup, 'text/html');

        $this->assertSame('html', $htmldoc->documentElement->localName);
        $this->assertSame(Namespaces::HTML, $htmldoc->documentElement->namespaceURI);
    }

    public function testDOMParserShouldHandleTheContentOfNoEmbedAsRawText(): void
    {
        $doc = (new DOMParser())->parseFromString('<noembed>&lt;a&gt;</noembed>', 'text/html');
        // $this->assertSame('&lt;a&gt;', $doc->querySelector('noembed')->textContent);
        $this->assertSame('&lt;a&gt;', $doc->getElementsByTagName('noembed')[0]->textContent);
    }

    public function testDOMParserThrowsOnInvalidEnumValue(): void
    {
        $this->expectException(TypeError::class);
        (new DOMParser())->parseFromString('', 'text/foo-this-is-invalid');
    }

    public function testScriptIsFoundSynchronouslyEvenWhenThereIsACSSImport(): void
    {
        $doc = (new DOMParser())->parseFromString(<<<'DOC_HTML'
<html><body>
<style>
    @import url(/dummy.css)
</style>
<script>document.x = 8<\/script>
</body></html>
DOC_HTML
, 'text/html');

        // $this->assertNotNull($doc->querySelector('script'));
        $this->assertNotNull($doc->getElementsByTagName('script')[0]);
    }

    public function testMustBeParsedWithScriptingDisabledSoNoscriptWorks(): void
    {
        $doc = (new DOMParser())->parseFromString(
            '<body><noscript><p id="test1">test1<p id="test2">test2</noscript>',
            'text/html'
        );

        $this->assertNode(
            $doc->body->firstChild->childNodes[0],
            ['type' => HTMLParagraphElement::class, 'id' => 'test1']
        );
        $this->assertNode(
            $doc->body->firstChild->childNodes[1],
            ['type' => HTMLParagraphElement::class, 'id' => 'test2']
        );
    }

    public function assertNode(Node $actual, array $expected): void
    {
        $this->assertInstanceOf($expected['type'], $actual);

        if (isset($expected['id'])) {
            $this->assertSame($expected['id'], $actual->id);
        }
    }

    public static function setUpBeforeClass(): void
    {
        self::$parser = new DOMParser();
        $input = '<html id="root"><head></head><body></body></html>';
        self::$doc = self::$parser->parseFromString($input, 'text/html');
    }

    public static function tearDownAfterClass(): void
    {
        self::$doc = null;
        self::$parser = null;
    }
}
