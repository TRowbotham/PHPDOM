<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\syntax\serializing_html_fragments;

use Closure;
use Generator;
use Rowbot\DOM\Tests\dom\WindowTrait;
use Rowbot\DOM\Tests\TestCase;

use function str_replace;

use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/syntax/serializing-html-fragments/serializing.html
 */
class SerializingTest extends TestCase
{
    use WindowTrait;

    private const EXPECTED = [
        ["", "<span></span>"],
        ["<a></a>", "<span><a></a></span>"],
        ["<a b=\"c\"></a>", "<span><a b=\"c\"></a></span>"],
        ["<a b=\"c\"></a>", "<span><a b=\"c\"></a></span>"],
        ["<a b=\"&amp;\"></a>", "<span><a b=\"&amp;\"></a></span>"],
        ["<a b=\"&nbsp;\"></a>", "<span><a b=\"&nbsp;\"></a></span>"],
        ["<a b=\"&quot;\"></a>", "<span><a b=\"&quot;\"></a></span>"],
        ["<a b=\"<\"></a>", "<span><a b=\"<\"></a></span>"],
        ["<a b=\">\"></a>", "<span><a b=\">\"></a></span>"],
        ["<a href=\"javascript:&quot;<>&quot;\"></a>", "<span><a href=\"javascript:&quot;<>&quot;\"></a></span>"],
        ["<svg xlink:href=\"a\"></svg>", "<span><svg xlink:href=\"a\"></svg></span>"],
        ["<svg xmlns:svg=\"test\"></svg>", "<span><svg xmlns:svg=\"test\"></svg></span>"],
        ["a", "<span>a</span>"],
        ["&amp;", "<span>&amp;</span>"],
        ["&nbsp;", "<span>&nbsp;</span>"],
        ["&lt;", "<span>&lt;</span>"],
        ["&gt;", "<span>&gt;</span>"],
        ["\"", "<span>\"</span>"],
        ["<style><&></style>", "<span><style><&></style></span>"],
        ["<script type=\"test\"><&></script>", "<span><script type=\"test\"><&></script></span>"],
        ["<&>", "<script type=\"test\"><&></script>"],
        ["<xmp><&></xmp>", "<span><xmp><&></xmp></span>"],
        ["<iframe><&></iframe>", "<span><iframe><&></iframe></span>"],
        ["<noembed><&></noembed>", "<span><noembed><&></noembed></span>"],
        ["<noframes><&></noframes>", "<span><noframes><&></noframes></span>"],
        ["<noscript><&></noscript>", "<span><noscript><&></noscript></span>"],
        ["<!--data-->", "<span><!--data--></span>"],
        ["<a><b><c></c></b><d>e</d><f><g>h</g></f></a>", "<span><a><b><c></c></b><d>e</d><f><g>h</g></f></a></span>"],
        ["", "<span b=\"c\"></span>"],
    ];
    private const TEXT_ELEMENTS = ["pre", "textarea", "listing"];
    private const VOID_ELEMENTS = [
        "area", "base", "basefont", "bgsound", "br", "col", "embed",
        "frame", "hr", "img", "input", "keygen", "link",
        "meta", "param", "source", "track", "wbr",
    ];

    /**
     * @dataProvider innerHTMLExpectedTestProvider
     * @dataProvider innerHTMLDOMTestProvider
     * @dataProvider innerHTMLTextCrossMapTestProvider
     * @dataProvider innerHTMLVoidCrossMapTestProvider
     */
    public function testInnerHTML(Closure $func, $elem, $expected): void
    {
        self::assertSame($expected, $func($elem)->innerHTML);
    }

    /**
     * @dataProvider outerHTMLExpectedTestProvider
     * @dataProvider outerHTMLDOMTestProvider
     * @dataProvider outerHTMLTextCrossMapTestProvider
     * @dataProvider outerHTMLVoidCrossMapTestProvider
     */
    public function testOuterHTML(Closure $func, $elem, $expected): void
    {
        self::assertSame($expected, $func($elem)->outerHTML);
    }

    public function innerHTMLExpectedTestProvider(): Generator
    {
        foreach (self::EXPECTED as $i => $item) {
            yield [
                static function () use ($i) {
                    return self::getWindow()->document->getElementById('test')->children[$i];
                },
                null,
                $item[0],
            ];
        }
    }

    public function outerHTMLExpectedTestProvider(): Generator
    {
        foreach (self::EXPECTED as $i => $item) {
            yield [
                static function () use ($i) {
                    return self::getWindow()->document->getElementById('test')->children[$i];
                },
                null,
                $item[1],
            ];
        }
    }

    public function innerHTMLDOMTestProvider(): Generator
    {
        foreach ($this->domTests() as $item) {
            yield [$item[1], null, $item[2]];
        }
    }

    public function outerHTMLDOMTestProvider(): Generator
    {
        foreach ($this->domTests() as $item) {
            yield [$item[1], null, $item[3]];
        }
    }

    public function innerHTMLTextCrossMapTestProvider(): array
    {
        $document = self::getWindow()->document;

        return $this->cross_map($this->text_tests(), self::TEXT_ELEMENTS, static function ($test_data, $elem_name) use ($document) {
            return [
                $test_data[1],
                $document->createElement($elem_name),
                str_replace('%text', $elem_name, $test_data[2]),
            ];
        });
    }

    public function outerHTMLTextCrossMapTestProvider(): array
    {
        $document = self::getWindow()->document;

        return $this->cross_map($this->text_tests(), self::TEXT_ELEMENTS, static function ($test_data, $elem_name) use ($document) {
            return [
                $test_data[1],
                $document->createElement($elem_name),
                str_replace('%text', $elem_name, $test_data[3]),
            ];
        });
    }

    public function innerHTMLVoidCrossMapTestProvider(): array
    {
        return $this->cross_map($this->void_tests(), self::VOID_ELEMENTS, function ($test_data, $elem_name) {
            return [
                $test_data[1],
                $this->make_void($elem_name),
                str_replace('%void', $elem_name, $test_data[2]),
            ];
        });
    }

    public function outerHTMLVoidCrossMapTestProvider(): array
    {
        return $this->cross_map($this->void_tests(), self::VOID_ELEMENTS, function ($test_data, $elem_name) {
            return [
                $test_data[1],
                $this->make_void($elem_name),
                str_replace('%void', $elem_name, $test_data[3]),
            ];
        });
    }

    public function cross_map($a1, $a2, $f)
    {
        $rv = [];

        foreach ($a1 as $a1_elem) {
            foreach ($a2 as $a2_elem) {
                $rv[] = $f($a1_elem, $a2_elem);
            }
        }

        return $rv;
    }

    public function make_void(string $name)
    {
        $document = self::getWindow()->document;
        $rv = $document->createElement($name);
        $rv->appendChild($document->createElement("a"))->appendChild($document->createComment("abc"));
        $rv->appendChild($document->createElement("b"))
            ->appendChild($document->createElement("c"))
            ->appendChild($document->createTextNode("abc"));

        return $rv;
    }

    public function domTests(): array
    {
        $document = self::getWindow()->document;

        return [
            ["Attribute in the XML namespace",
                static function () use ($document) {
                     $span = $document->createElement("span");
                     $svg = $document->createElement("svg");
                     $svg->setAttributeNS("http://www.w3.org/XML/1998/namespace", "xml:foo", "test");
                     $span->appendChild($svg);

                     return $span;
                },
             '<svg xml:foo="test"></svg>',
             '<span><svg xml:foo="test"></svg></span>'],

            ["Attribute in the XML namespace with the prefix not set to xml:",
                static function () use ($document) {
                     $span = $document->createElement("span");
                     $svg = $document->createElement("svg");
                     $svg->setAttributeNS("http://www.w3.org/XML/1998/namespace", "abc:foo", "test");
                     $span->appendChild($svg);

                     return $span;
                },
             '<svg xml:foo="test"></svg>',
             '<span><svg xml:foo="test"></svg></span>'],

             ["Non-'xmlns' attribute in the xmlns namespace",
                static function () use ($document) {
                    $span = $document->createElement("span");
                    $svg = $document->createElement("svg");
                    $svg->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:foo", "test");
                    $span->appendChild($svg);

                    return $span;
                },
             '<svg xmlns:foo="test"></svg>',
             '<span><svg xmlns:foo="test"></svg></span>'],

             ["'xmlns' attribute in the xmlns namespace",
                static function () use ($document) {
                    $span = $document->createElement("span");
                    $svg = $document->createElement("svg");
                    $svg->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns", "test");
                    $span->appendChild($svg);

                    return $span;
                },
             '<svg xmlns="test"></svg>',
             '<span><svg xmlns="test"></svg></span>'],

            ["Attribute in non-standard namespace",
                static function () use ($document) {
                    $span = $document->createElement("span");
                    $svg = $document->createElement("svg");
                    $svg->setAttributeNS("fake_ns", "abc:def", "test");
                    $span->appendChild($svg);

                    return $span;
                },
             '<svg abc:def="test"></svg>',
             '<span><svg abc:def="test"></svg></span>'],

            ["<span> starting with U+000A",
                static function () use ($document) {
                    $elem = $document->createElement("span");
                    $elem->appendChild($document->createTextNode("\x0A"));

                    return $elem;
                },
             "\x0A",
             "<span>\x0A</span>"],

              //TODO: Processing instructions
        ];
    }

    public function text_tests(): array
    {
        $document = self::getWindow()->document;

        return [
            ["<%text> context starting with U+000A",
                static function ($elem) use ($document) {
                    $elem->appendChild($document->createTextNode("\x0A"));

                    return $elem;
                },
             "\x0A",
             "<%text>\x0A</%text>"],

            ["<%text> context not starting with U+000A",
                static function ($elem) use ($document) {
                    $elem->appendChild($document->createTextNode("a\x0A"));

                    return $elem;
                },
             "a\x0A",
             "<%text>a\x0A</%text>"],

            ["<%text> non-context starting with U+000A",
                static function ($elem) use ($document) {
                    $span = $document->createElement("span");
                    $elem->appendChild($document->createTextNode("\x0A"));
                    $span->appendChild($elem);

                    return $span;
                },
             "<%text>\x0A</%text>",
             "<span><%text>\x0A</%text></span>"],

            ["<%text> non-context not starting with U+000A",
                static function ($elem) use ($document) {
                    $span = $document->createElement("span");
                    $elem->appendChild($document->createTextNode("a\x0A"));
                    $span->appendChild($elem);

                    return $span;
                },
             "<%text>a\x0A</%text>",
             "<span><%text>a\x0A</%text></span>"],
        ];
    }

    public function void_tests(): array
    {
        $document = self::getWindow()->document;

        return [
            ["Void context node",
                static function ($void_elem) {
                     return $void_elem;
                },
             "",
             "<%void>",
            ],
            ["void as first child with following siblings",
                static function ($void_elem) use ($document) {
                     $span = $document->createElement("span");
                     $span->appendChild($void_elem);
                     $span->appendChild($document->createElement("a"))->appendChild($document->createTextNode("test"));
                     $span->appendChild($document->createElement("b"));

                     return $span;
                },
             "<%void><a>test</a><b></b>",
             "<span><%void><a>test</a><b></b></span>",
            ],
            ["void as second child with following siblings",
                static function ($void_elem) use ($document) {
                     $span = $document->createElement("span");
                     $span->appendChild($document->createElement("a"))->appendChild($document->createTextNode("test"));
                     $span->appendChild($void_elem);
                     $span->appendChild($document->createElement("b"));

                     return $span;
                },
             "<a>test</a><%void><b></b>",
             "<span><a>test</a><%void><b></b></span>",
            ],
            ["void as last child with preceding siblings",
                static function ($void_elem) use ($document) {
                      $span = $document->createElement("span");
                      $span->appendChild($document->createElement("a"))->appendChild($document->createTextNode("test"));
                      $span->appendChild($document->createElement("b"));
                      $span->appendChild($void_elem);

                      return $span;
                },
             "<a>test</a><b></b><%void>",
             "<span><a>test</a><b></b><%void></span>",
            ],
        ];
    }

    public static function getDocumentName(): string
    {
        return 'serializing.html';
    }

    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources';
    }
}
