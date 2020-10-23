<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use ArgumentCountError;
use Generator;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;
use TypeError;

use function file_get_contents;

use const DIRECTORY_SEPARATOR as DS;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/createContextualFragment.html
 */
class RangeCreateContextualFragmentTest extends TestCase
{
    private static $document;

    /**
     * @doesNotPerformAssertions
     */
    public function testMustNotThrowForDetachedNode(): void
    {
        $range = self::getDocument()->createRange();
        $range->detach();
        $range->createContextualFragment('');
    }

    public function testMustThrowWhenCallingWithoutArgs(): void
    {
        $this->expectException(ArgumentCountError::class);
        $range = self::getDocument()->createRange();
        $range->createContextualFragment();
    }

    public function testSimpleTestWithParagraphs(): void
    {
        // Simple test
        $document = self::getDocument();
        $range = $document->createRange();
        $range->selectNodeContents($document->body);

        $fragment = "<p CLaSs=testclass> Hi! <p>Hi!";
        $expected = $document->createDocumentFragment();
        $tmpNode = $document->createElement('p');
        $tmpNode->setAttribute('class', 'testclass');
        $tmpNode->appendChild($document->createTextNode(" Hi! "));
        $expected->appendChild($tmpNode);

        $tmpNode = $document->createElement('p');
        $tmpNode->appendChild($document->createTextNode("Hi!"));
        $expected->appendChild($tmpNode);

        $result = $range->createContextualFragment($fragment);
        $this->assertTrue($expected->isEqualNode($result));

        // Token test that the end node makes no difference
        $range->setEnd($document->body->getElementsByTagName('script')[0], 0);
        $result = $range->createContextualFragment($fragment);
        $this->assertTrue($expected->isEqualNode($result));
    }

    public function testDontAutoCreateBodyWhenAppliedtoHtml(): void
    {
        $document = self::getDocument();

        // This test based on https://bugzilla.mozilla.org/show_bug.cgi?id=585819,
        // from a real-world compat bug
        $range = $document->createRange();
        $range->selectNodeContents($document->documentElement);
        $fragment = "<span>Hello world</span>";
        $expected = $document->createDocumentFragment();
        $tmpNode = $document->createElement('span');
        $tmpNode->appendChild($document->createTextNode('Hello world'));
        $expected->appendChild($tmpNode);

        $result = $range->createContextualFragment($fragment);
        self::assertTrue($expected->isEqualNode($result));

        // Another token test that the end node makes no difference
        $range->setEnd($document->head, 0);
        $result = $range->createContextualFragment($fragment);
        self::assertTrue($expected->isEqualNode($result));
    }

    // Historical bugs in browsers; see https://github.com/whatwg/html/issues/2222

    /**
     * @doesNotPerformAssertions
     *
     * @dataProvider voidElementProvider
     */
    public function testCreateContextualFragmentShouldWorkOnContext(string $name): void
    {
        $document = self::getDocument();
        $range = $document->createRange();
        $contextNode = $document->createElement($name);
        $selectedNode = $document->createElement('div');
        $contextNode->appendChild($selectedNode);
        $range->selectNode($selectedNode);
        $range->createContextualFragment('some text');
    }

    /**
     * @dataProvider fragmentsProvider
     */
    public function testEquivalence(
        string $description,
        Node $element1,
        ?string $fragment1,
        Node $element2,
        ?string $fragment2
    ): void {
        $range1 = $element1->ownerDocument->createRange();
        $range1->selectNodeContents($element1);
        $range2 = $element2->ownerDocument->createRange();
        $range2->selectNodeContents($element2);

        if ($fragment1 === null) {
            $this->expectException(TypeError::class);
        }

        $result1 = $range1->createContextualFragment($fragment1);
        $result2 = $range2->createContextualFragment($fragment2);

        self::assertTrue($result1->isEqualNode($result2));

        // Throw in partial ownerDocument tests on the side, since the algorithm
        // does specify that and we don't want to completely not test it.
        if ($result1->firstChild !== null) {
            self::assertSame($element1->ownerDocument, $result1->firstChild->ownerDocument);
        }

        if ($result2->firstChild !== null) {
            self::assertSame($element2->ownerDocument, $result2->firstChild->ownerDocument);
        }
    }

    /**
     * @dataProvider fragmentProvider
     */
    public function fragmentsProvider(): array
    {
        $document = self::getDocument();

        $doc_fragment = $document->createDocumentFragment();
        $comment = $document->createComment("~o~");
        $doc_fragment->appendChild($comment);

        return [
            [
                "<html> and <body> must work the same, 1",
                $document->documentElement,
                "<span>Hello world</span>",
                $document->body,
                "<span>Hello world</span>",
            ],
            [
                "<html> and <body> must work the same, 2",
                $document->documentElement,
                "<body><p>Hello world",
                $document->body,
                "<body><p>Hello world",
            ],
            [
                "Implicit <body> creation",
                $document->documentElement,
                "<body><p>",
                $document->documentElement,
                "<p>",
            ],
            [
                "Namespace generally shouldn't matter",
                $document->createElementNS("http://fake-namespace", "div"),
                "<body><p><span>Foo",
                $document->createElement("div"),
                "<body><p><span>Foo",
            ],
            [
                "<html> in a different namespace shouldn't be special",
                $document->createElementNS("http://fake-namespace", "html"),
                "<body><p>",
                $document->createElement("div"),
                "<body><p>",
            ],
            [
                "SVG namespace shouldn't be special",
                $document->createElementNS("http://www.w3.org/2000/svg", "div"),
                "<body><p>",
                $document->createElement("div"),
                "<body><p>",
            ],
            [
                "null should be stringified",
                $document->createElement("span"),
                null,
                $document->createElement("span"),
                "null",
            ],
            // [
            //     "undefined should be stringified",
            //     $document->createElement("span"),
            //     undefined,
            //     $document->createElement("span"),
            //     "undefined",
            // ],
            [
                "Text nodes shouldn't be special",
                $document->createTextNode("?"),
                "<body><p>",
                $document->createElement("div"),
                "<body><p>",
            ],
            [
                "Non-Element parent should not be special",
                $comment,
                "<body><p>",
                $document->createElement("div"),
                "<body><p>",
            ],
        ];
    }

    public function voidElementProvider(): Generator
    {
        $elements = [
            // Void
            "area",
            "base",
            "basefont",
            "bgsound",
            "br",
            "col",
            "embed",
            "frame",
            "hr",
            "img",
            "input",
            "keygen",
            "link",
            "meta",
            "param",
            "source",
            "track",
            "wbr",

            // Historical
            "menuitem",
            "image",
        ];

        foreach ($elements as $element) {
            yield [$element];
        }
    }

    public static function getDocument(): HTMLDocument
    {
        if (self::$document) {
            return self::$document;
        }

        $parser = new DOMParser();
        self::$document = $parser->parseFromString(
            file_get_contents(__DIR__ . DS . 'html' . DS . 'createContextualFragment.html'),
            'text/html'
        );

        return self::$document;
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
