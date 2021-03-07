<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Generator;
use ReflectionObject;
use ReflectionProperty;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\WindowTrait;

use function count;
use function extract;
use function in_array;
use function mb_strlen;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Node-properties.html
 */
class NodePropertiesTest extends NodeTestCase
{
    use WindowTrait;

    // Mostly deprecated or irrelevant properties
    private const IGNORE = ['compatMode'];

    /**
     * @dataProvider nodePropertiesProvider
     */
    public function testNodeProperties(string $node, array $nodeData): void
    {
        $window = self::getWindow();

        foreach ($nodeData as $prop => $expected) {
            if (in_array($prop, self::IGNORE, true)) {
                continue;
            }

            $this->assertSame($window->eval("{$node}->{$prop}"), $expected);
        }
    }

    public function nodePropertiesProvider(): Generator
    {
        $window = self::getWindow();
        $window->setupRangeTests();

        // Lets butcher the local symbol table!
        $reflection = new ReflectionObject($window);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $props = [];

        foreach ($properties as $property) {
            $props[$property->getName()] = $property->getValue($window);
        }

        $extractCount = extract($props);
        assert($extractCount === count($properties));

        /**
         * First we define a data structure to tell us what tests to run.  The keys
         * will be eval()ed, and are mostly global variables defined in common.js.  The
         * values are objects, which maps properties to expected values.  So
         *
         *     'foo' => [
         *         bar: "baz",
         *         quz: 7,
         *     ],
         *
         * will test that eval("foo.bar") === "baz" and eval("foo.quz") === 7.  "foo"
         * and "bar" could thus be expressions, like "document.documentElement" and
         * "childNodes[4]" respectively.
         *
         * To avoid repetition, some values are automatically added based on others.
         * For instance, if we specify 'nodeType' => Node::TEXT_NODE, we'll automatically
         * also test nodeName: "#text".  This is handled by code after this variable is
         * defined.
         */
        $expected = [
            'testDiv' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $document->body,
                'parentElement' => $document->body,
                "childNodes->length" => 6,
                'childNodes[0]' => $paras[0],
                'childNodes[1]' => $paras[1],
                'childNodes[2]' => $paras[2],
                'childNodes[3]' => $paras[3],
                'childNodes[4]' => $paras[4],
                'childNodes[5]' => $comment,
                'previousSibling' => null,
                'nextSibling' => $document->getElementById("log"),
                'textContent' => "A\u{0308}b\u{0308}c\u{0308}d\u{0308}e\u{0308}f\u{0308}g\u{0308}h\u{0308}\nIjklmnop\nQrstuvwxYzabcdefGhijklmn",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "div",
                'tagName' => "DIV",
                'id' => "test",
                'children[0]' => $paras[0],
                'children[1]' => $paras[1],
                'children[2]' => $paras[2],
                'children[3]' => $paras[3],
                'children[4]' => $paras[4],
                'previousElementSibling' => null,
                // nextSibling isn't explicitly set
                //'nextElementSibling' => ,
                'childElementCount' => 5,
            ],
            'detachedDiv' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => null,
                'parentElement' => null,
                "childNodes->length" => 2,
                'childNodes[0]' => $detachedPara1,
                'childNodes[1]' => $detachedPara2,
                'previousSibling' => null,
                'nextSibling' => null,
                'textContent' => "OpqrstuvWxyzabcd",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "div",
                'tagName' => "DIV",
                'children[0]' => $detachedPara1,
                'children[1]' => $detachedPara2,
                'previousElementSibling' => null,
                'nextElementSibling' => null,
                'childElementCount' => 2,
            ],
            'detachedPara1' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $detachedDiv,
                'parentElement' => $detachedDiv,
                "childNodes->length" => 1,
                'previousSibling' => null,
                'nextSibling' => $detachedPara2,
                'textContent' => "Opqrstuv",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'previousElementSibling' => null,
                'nextElementSibling' => $detachedPara2,
                'childElementCount' => 0,
            ],
            'detachedPara2' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $detachedDiv,
                'parentElement' => $detachedDiv,
                "childNodes->length" => 1,
                'previousSibling' => $detachedPara1,
                'nextSibling' => null,
                'textContent' => "Wxyzabcd",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'previousElementSibling' => $detachedPara1,
                'nextElementSibling' => null,
                'childElementCount' => 0,
            ],
            'document' => [
                // Node
                'nodeType' => Node::DOCUMENT_NODE,
                "childNodes->length" => 2,
                'childNodes[0]' => $document->doctype,
                'childNodes[1]' => $document->documentElement,

                // Document
                // 'URL' => String(location),
                'URL' => '',
                'compatMode' => "CSS1Compat",
                'characterSet' => "UTF-8",
                'contentType' => "text/html",
                'doctype' => $doctype,
                //'documentElement' => ,
            ],
            'foreignDoc' => [
                // Node
                'nodeType' => Node::DOCUMENT_NODE,
                "childNodes->length" => 3,
                'childNodes[0]' => $foreignDoc->doctype,
                'childNodes[1]' => $foreignDoc->documentElement,
                'childNodes[2]' => $foreignComment,

                // Document
                'URL' => "about:blank",
                'compatMode' => "CSS1Compat",
                'characterSet' => "UTF-8",
                'contentType' => "text/html",
                //'doctype' => ,
                //'documentElement' => ,
            ],
            'foreignPara1' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $foreignDoc,
                'parentNode' => $foreignDoc->body,
                'parentElement' => $foreignDoc->body,
                "childNodes->length" => 1,
                'previousSibling' => null,
                'nextSibling' => $foreignPara2,
                'textContent' => "Efghijkl",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'previousElementSibling' => null,
                'nextElementSibling' => $foreignPara2,
                'childElementCount' => 0,
            ],
            'foreignPara2' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $foreignDoc,
                'parentNode' => $foreignDoc->body,
                'parentElement' => $foreignDoc->body,
                "childNodes->length" => 1,
                'previousSibling' => $foreignPara1,
                'nextSibling' => $foreignTextNode,
                'textContent' => "Mnopqrst",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'previousElementSibling' => $foreignPara1,
                'nextElementSibling' => null,
                'childElementCount' => 0,
            ],
            'xmlDoc' => [
                // Node
                'nodeType' => Node::DOCUMENT_NODE,
                "childNodes->length" => 4,
                'childNodes[0]' => $xmlDoctype,
                'childNodes[1]' => $xmlElement,
                'childNodes[2]' => $processingInstruction,
                'childNodes[3]' => $xmlComment,

                // Document
                'URL' => "about:blank",
                'compatMode' => "CSS1Compat",
                'characterSet' => "UTF-8",
                'contentType' => "application/xml",
                //'doctype' => ,
                //'documentElement' => ,
            ],
            'xmlElement' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => $xmlDoc,
                'parentElement' => null,
                "childNodes->length" => 1,
                'childNodes[0]' => $xmlTextNode,
                'previousSibling' => $xmlDoctype,
                'nextSibling' => $processingInstruction,
                'textContent' => "do re mi fa so la ti",

                // Element
                'namespaceURI' => null,
                'prefix' => null,
                'localName' => "igiveuponcreativenames",
                'tagName' => "igiveuponcreativenames",
                'previousElementSibling' => null,
                'nextElementSibling' => null,
                'childElementCount' => 0,
            ],
            'detachedXmlElement' => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => null,
                'parentElement' => null,
                "childNodes->length" => 0,
                'previousSibling' => null,
                'nextSibling' => null,
                'textContent' => "",

                // Element
                'namespaceURI' => null,
                'prefix' => null,
                'localName' => "everyone-hates-hyphenated-element-names",
                'tagName' => "everyone-hates-hyphenated-element-names",
                'previousElementSibling' => null,
                'nextElementSibling' => null,
                'childElementCount' => 0,
            ],
            'detachedTextNode' => [
                // Node
                'nodeType' => Node::TEXT_NODE,
                'ownerDocument' => $document,
                'parentNode' => null,
                'parentElement' => null,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "Uvwxyzab",

                // Text
                'wholeText' => "Uvwxyzab",
            ],
            'foreignTextNode' => [
                // Node
                'nodeType' => Node::TEXT_NODE,
                'ownerDocument' => $foreignDoc,
                'parentNode' => $foreignDoc->body,
                'parentElement' => $foreignDoc->body,
                'previousSibling' => $foreignPara2,
                'nextSibling' => null,
                'nodeValue' => "I admit that I harbor doubts about whether we really need so many things to test, but it's too late to stop now.",

                // Text
                'wholeText' => "I admit that I harbor doubts about whether we really need so many things to test, but it's too late to stop now.",
            ],
            'detachedForeignTextNode' => [
                // Node
                'nodeType' => Node::TEXT_NODE,
                'ownerDocument' => $foreignDoc,
                'parentNode' => null,
                'parentElement' => null,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "Cdefghij",

                // Text
                'wholeText' => "Cdefghij",
            ],
            'xmlTextNode' => [
                // Node
                'nodeType' => Node::TEXT_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => $xmlElement,
                'parentElement' => $xmlElement,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "do re mi fa so la ti",

                // Text
                'wholeText' => "do re mi fa so la ti",
            ],
            'detachedXmlTextNode' => [
                // Node
                'nodeType' => Node::TEXT_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => null,
                'parentElement' => null,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "Klmnopqr",

                // Text
                'wholeText' => "Klmnopqr",
            ],
            'processingInstruction' => [
                // Node
                'nodeType' => Node::PROCESSING_INSTRUCTION_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => $xmlDoc,
                'parentElement' => null,
                'previousSibling' => $xmlElement,
                'nextSibling' => $xmlComment,
                'nodeValue' => 'Did you know that ":syn sync fromstart" is very useful when using vim to edit large amounts of JavaScript embedded in HTML?',

                // ProcessingInstruction
                'target' => "somePI",
            ],
            'detachedProcessingInstruction' => [
                // Node
                'nodeType' => Node::PROCESSING_INSTRUCTION_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => null,
                'parentElement' => null,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "chirp chirp chirp",

                // ProcessingInstruction
                'target' => "whippoorwill",
            ],
            'comment' => [
                // Node
                'nodeType' => Node::COMMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $testDiv,
                'parentElement' => $testDiv,
                'previousSibling' => $paras[4],
                'nextSibling' => null,
                'nodeValue' => "Alphabet soup?",
            ],
            'detachedComment' => [
                // Node
                'nodeType' => Node::COMMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => null,
                'parentElement' => null,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "Stuvwxyz",
            ],
            'foreignComment' => [
                // Node
                'nodeType' => Node::COMMENT_NODE,
                'ownerDocument' => $foreignDoc,
                'parentNode' => $foreignDoc,
                'parentElement' => null,
                'previousSibling' => $foreignDoc->documentElement,
                'nextSibling' => null,
                'nodeValue' => '"Commenter" and "commentator" mean different things.  I\'ve seen non-native speakers trip up on this.',
            ],
            'detachedForeignComment' => [
                // Node
                'nodeType' => Node::COMMENT_NODE,
                'ownerDocument' => $foreignDoc,
                'parentNode' => null,
                'parentElement' => null,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "אריה יהודה",
            ],
            'xmlComment' => [
                // Node
                'nodeType' => Node::COMMENT_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => $xmlDoc,
                'parentElement' => null,
                'previousSibling' => $processingInstruction,
                'nextSibling' => null,
                'nodeValue' => "I maliciously created a comment that will break incautious XML serializers, but Firefox threw an exception, so all I got was this lousy T-shirt",
            ],
            'detachedXmlComment' => [
                // Node
                'nodeType' => Node::COMMENT_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => null,
                'parentElement' => null,
                'previousSibling' => null,
                'nextSibling' => null,
                'nodeValue' => "בן חיים אליעזר",
            ],
            'docfrag' => [
                // Node
                'nodeType' => Node::DOCUMENT_FRAGMENT_NODE,
                'ownerDocument' => $document,
                "childNodes->length" => 0,
                'textContent' => "",
            ],
            'foreignDocfrag' => [
                // Node
                'nodeType' => Node::DOCUMENT_FRAGMENT_NODE,
                'ownerDocument' => $foreignDoc,
                "childNodes->length" => 0,
                'textContent' => "",
            ],
            'xmlDocfrag' => [
                // Node
                'nodeType' => Node::DOCUMENT_FRAGMENT_NODE,
                'ownerDocument' => $xmlDoc,
                "childNodes->length" => 0,
                'textContent' => "",
            ],
            'doctype' => [
                // Node
                'nodeType' => Node::DOCUMENT_TYPE_NODE,
                'ownerDocument' => $document,
                'parentNode' => $document,
                'previousSibling' => null,
                'nextSibling' => $document->documentElement,

                // DocumentType
                'name' => "html",
                'publicId' => "",
                'systemId' => "",
            ],
            'foreignDoctype' => [
                // Node
                'nodeType' => Node::DOCUMENT_TYPE_NODE,
                'ownerDocument' => $foreignDoc,
                'parentNode' => $foreignDoc,
                'previousSibling' => null,
                'nextSibling' => $foreignDoc->documentElement,

                // DocumentType
                'name' => "html",
                'publicId' => "",
                'systemId' => "",
            ],
            'xmlDoctype' => [
                // Node
                'nodeType' => Node::DOCUMENT_TYPE_NODE,
                'ownerDocument' => $xmlDoc,
                'parentNode' => $xmlDoc,
                'previousSibling' => null,
                'nextSibling' => $xmlElement,

                // DocumentType
                'name' => "qorflesnorf",
                'publicId' => "abcde",
                'systemId' => "x\"'y",
            ],
            "paras[0]" => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $testDiv,
                'parentElement' => $testDiv,
                "childNodes->length" => 1,
                'previousSibling' => null,
                'nextSibling' => $paras[1],
                'textContent' => "A\u{0308}b\u{0308}c\u{0308}d\u{0308}e\u{0308}f\u{0308}g\u{0308}h\u{0308}\n",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'id' => "a",
                'previousElementSibling' => null,
                'nextElementSibling' => $paras[1],
                'childElementCount' => 0,
            ],
            "paras[1]" => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $testDiv,
                'parentElement' => $testDiv,
                "childNodes->length" => 1,
                'previousSibling' => $paras[0],
                'nextSibling' => $paras[2],
                'textContent' => "Ijklmnop\n",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'id' => "b",
                'previousElementSibling' => $paras[0],
                'nextElementSibling' => $paras[2],
                'childElementCount' => 0,
            ],
            "paras[2]" => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $testDiv,
                'parentElement' => $testDiv,
                "childNodes->length" => 1,
                'previousSibling' => $paras[1],
                'nextSibling' => $paras[3],
                'textContent' => "Qrstuvwx",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'id' => "c",
                'previousElementSibling' => $paras[1],
                'nextElementSibling' => $paras[3],
                'childElementCount' => 0,
            ],
            "paras[3]" => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $testDiv,
                'parentElement' => $testDiv,
                "childNodes->length" => 1,
                'previousSibling' => $paras[2],
                'nextSibling' => $paras[4],
                'textContent' => "Yzabcdef",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'id' => "d",
                'previousElementSibling' => $paras[2],
                'nextElementSibling' => $paras[4],
                'childElementCount' => 0,
            ],
            "paras[4]" => [
                // Node
                'nodeType' => Node::ELEMENT_NODE,
                'ownerDocument' => $document,
                'parentNode' => $testDiv,
                'parentElement' => $testDiv,
                "childNodes->length" => 1,
                'previousSibling' => $paras[3],
                'nextSibling' => $comment,
                'textContent' => "Ghijklmn",

                // Element
                'namespaceURI' => "http://www.w3.org/1999/xhtml",
                'prefix' => null,
                'localName' => "p",
                'tagName' => "P",
                'id' => "e",
                'previousElementSibling' => $paras[3],
                'nextElementSibling' => null,
                'childElementCount' => 0,
            ],
        ];

        foreach ($expected as $node => $_) {
            switch ($expected[$node]['nodeType']) {
                case Node::ELEMENT_NODE:
                    $expected[$node]['nodeName'] = $expected[$node]['tagName'];
                    $expected[$node]['nodeValue'] = null;
                    $expected[$node]['children->length'] = $expected[$node]['childElementCount'];

                    if (!isset($expected[$node]['id'])) {
                        $expected[$node]['id'] = '';
                    }

                    if (!isset($expected[$node]['className'])) {
                        $expected[$node]['className'] = '';
                    }

                    $len = $expected[$node]['childElementCount'];

                    if ($len === 0) {
                        $expected[$node]['firstElementChild'] = null;
                        $expected[$node]['lastElementChild'] = null;
                    } else {
                        // If we have expectations for the first/last child in children,
                        // use those.  Otherwise, at least check that .firstElementChild ==
                        // .children[0] and .lastElementChild == .children[len - 1], even
                        // if we aren't sure what they should be.
                        $expected[$node]['firstElementChild'] = $expected[$node]['children[0]']
                            ? $expected[$node]['children[0]']
                            : eval('return $' . $node . '->children[' . ($len - 1) . '];');
                        $expected[$node]['lastElementChild'] = $expected[$node]['children[' . ($len - 1) . ']']
                            ? $expected[$node]['children[' . ($len - 1) . ']']
                            : eval('return $' . $node . '->children[' . ($len - 1) . '];');
                    }

                    break;

                case Node::TEXT_NODE:
                    $expected[$node]['nodeName'] = '#text';
                    $expected[$node]['childNodes->length'] = 0;
                    $expected[$node]['textContent'] = $expected[$node]['data'] = $expected[$node]['nodeValue'];
                    $expected[$node]['length'] = mb_strlen($expected[$node]['nodeValue'], 'utf-8');

                    break;

                case Node::PROCESSING_INSTRUCTION_NODE:
                    $expected[$node]['nodeName'] = $expected[$node]['target'];
                    $expected[$node]['childNodes->length'] = 0;
                    $expected[$node]['textContent'] = $expected[$node]['data'] = $expected[$node]['nodeValue'];
                    $expected[$node]['length'] = mb_strlen($expected[$node]['nodeValue'], 'utf-8');

                    break;

                case Node::COMMENT_NODE:
                    $expected[$node]['nodeName'] = '#comment';
                    $expected[$node]['childNodes->length'] = 0;
                    $expected[$node]['textContent'] = $expected[$node]['data'] = $expected[$node]['nodeValue'];
                    $expected[$node]['length'] = mb_strlen($expected[$node]['nodeValue'], 'utf-8');

                    break;

                case Node::DOCUMENT_NODE:
                    $expected[$node]['nodeName'] = '#document';
                    $expected[$node]['ownerDocument'] = $expected[$node]['parentNode'] =
                        $expected[$node]['parentElement'] = $expected[$node]['previousSibling'] =
                        $expected[$node]['nextSibling'] = $expected[$node]['nodeValue'] =
                        $expected[$node]['textContent'] = null;
                    $expected[$node]['documentURI'] = $expected[$node]['URL'];
                    $expected[$node]['charset'] = $expected[$node]['inputEncoding'] =
                        $expected[$node]['characterSet'];

                    break;

                case Node::DOCUMENT_TYPE_NODE:
                    $expected[$node]['nodeName'] = $expected[$node]['name'];
                    $expected[$node]['childNodes->length'] = 0;
                    $expected[$node]['parentElement'] = $expected[$node]['nodeValue'] =
                        $expected[$node]['textContent'] = null;

                    break;

                case Node::DOCUMENT_FRAGMENT_NODE:
                    $expected[$node]['nodeName'] = '#document-fragment';
                    $expected[$node]['parentNode'] = $expected[$node]['parentElement'] =
                        $expected[$node]['previousSibling'] = $expected[$node]['nextSibling'] =
                        $expected[$node]['nodeValue'] = null;

                    break;
            }

            // Now we set some further default values that are independent of node
            // type.
            $len = $expected[$node]['childNodes->length'];

            if ($len === 0) {
                $expected[$node]['firstChild'] = $expected[$node]['lastChild'] = null;
            } else {
                // If we have expectations for the first/last child in childNodes, use
                // those.  Otherwise, at least check that .firstChild == .childNodes[0]
                // and .lastChild == .childNodes[len - 1], even if we aren't sure what
                // they should be.
                $expected[$node]['firstChild'] = isset($expected[$node]['childNodes[0]'])
                    ? $expected[$node]['childNodes[0]']
                    : eval('return $' . $node . '->childNodes[0];');
                $expected[$node]['lastChild'] = isset($expected[$node]['childNodes[' . ($len - 1) . ']'])
                    ? $expected[$node]['childNodes[' . ($len - 1) . ']']
                    : eval('return $' . $node . '->childNodes[' . ($len - 1) . '];');
            }

            $expected[$node]['hasChildNodes()'] = !!$expected[$node]['childNodes->length'];
        }

        foreach ($expected as $node => $data) {
            yield [$node, $data];
        }
    }

    public static function getDocumentName(): string
    {
        return 'Node-properties.html';
    }
}
