<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Element\Element;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\Utils;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Document-createElement.html
 */
class DocumentCreateElementTest extends TestCase
{
    use DocumentGetter;

    public function validNamesProvider()
    {
        return [
            // [null],
            ["foo"],
            ["f1oo"],
            ["foo1"],
            ["f\u{0BC6}"],
            ["foo\u{0BC6}"],
            [":"],
            [":foo"],
            ["f:oo"],
            ["foo:"],
            ["f:o:o"],
            ["f::oo"],
            ["f::oo:"],
            ["foo:0"],
            ["foo:_"],
            // combining char after :, invalid QName but valid Name
            ["foo:\u{0BC6}"],
            ["foo:foo\u{0BC6}"],
            ["foo\u{0BC6}:foo"],
            ["xml"],
            ["xmlns"],
            ["xmlfoo"],
            ["xml:foo"],
            ["xmlns:foo"],
            ["xmlfoo:bar"],
            ["svg"],
            ["math"],
            ["FOO"],
            // Test that non-ASCII chars don't get uppercased/lowercased
            ["mar\u{212a}"],
            ["\u{0130}nput"],
            ["\u{0131}nput"]
        ];
    }

    public function getWin($desc)
    {
        $document = $this->getHTMLDocument();

        if ($desc === 'HTML document') {
            return $document;
        }

        if ($desc === 'XML document') {
            return $document->implementation->createDocument(null, null, null);
        }

        if ($desc === 'XHTML document') {
            return $document->implementation->createDocument(
                Namespaces::HTML,
                '',
                $document->implementation->createDocumentType('html', '', '')
            );
        }
    }

    public function getDocumentDescription()
    {
        return [
            'HTML document',
            'XML document',
            'XHTML document'
        ];
    }

    /**
     * @dataProvider validNamesProvider
     */
    public function testValidNames($t)
    {
        foreach ($this->getDocumentDescription() as $desc) {
            $doc = $this->getWin($desc);
            $elt = $doc->createElement($t);
            $this->assertInstanceOf(Element::class, $elt);
            $this->assertInstanceOf(Node::class, $elt);
            $localName = $desc === 'HTML document'
                ? Utils::toASCIILowercase((string) $t)
                : (string) $t;
            $this->assertEquals($localName, $elt->localName);
            $tagName = $desc === 'HTML document'
                ? Utils::toASCIIUppercase((string) $t)
                : (string) $t;
            $this->assertEquals($tagName, $elt->tagName);
            $this->assertNull($elt->prefix);
            $namespace = $desc === 'XML document'
                ? null
                : Namespaces::HTML;
            $this->assertEquals($namespace, $elt->namespaceURI);
        }
    }

    public function invalidNamesProvider()
    {
        return [
            [""],
            ["1foo"],
            ["1:foo"],
            ["fo o"],
            ["\u{0300}foo"],
            ["}foo"],
            ["f}oo"],
            ["foo}"],
            ["\u{ffff}foo"],
            ["f\u{ffff}oo"],
            ["foo\u{ffff}"],
            ["<foo"],
            ["foo>"],
            ["<foo>"],
            ["f<oo"],
            ["-foo"],
            [".foo"],
            ["\u{0300}"]
        ];
    }

    /**
     * @dataProvider invalidNamesProvider
     */
    public function testInvalidNames($arg)
    {
        foreach ($this->getDocumentDescription() as $desc) {
            $doc = $this->getWin($desc);
            $this->assertThrows(function () use ($doc, $arg) {
                $doc->createElement($arg);
            }, InvalidCharacterError::class);
        }
    }
}
