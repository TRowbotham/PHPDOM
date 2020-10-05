<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Namespaces;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use TypeError;

use function array_merge;
use function explode;
use function mb_strpos;

class DOMImplementationCreateDocumentTest extends TestCase
{
    use CreateElementNSTests;
    use DocumentGetter;

    protected $tests;

    /**
     * @dataProvider getTestData
     */
    public function testCreateDocument(
        ?string $namespace,
        ?string $qualifiedName,
        $doctype,
        ?string $expected = null
    ): void {
        $document = $this->getHTMLDocument();

        if ($expected) {
            $this->expectException($expected);
            $document->implementation->createDocument($namespace, $qualifiedName, $doctype);
        } else {
            $doc = $document->implementation->createDocument($namespace, $qualifiedName, $doctype);

            $this->assertSame(Node::DOCUMENT_NODE, $doc->nodeType, 'nodeType');
            $this->assertSame($doc::DOCUMENT_NODE, $doc->nodeType, 'nodeType');
            $this->assertSame('#document', $doc->nodeName, 'nodeName');
            $this->assertNull($doc->nodeValue, 'nodeValue');

            $omitRootElement = $qualifiedName === null || (string) $qualifiedName === '';

            if ($omitRootElement) {
                $this->assertNull($doc->documentElement, 'documentElement');
            } else {
                $element = $doc->documentElement;
                $this->assertNotNull($element, 'documentElement');
                $this->assertSame(Node::ELEMENT_NODE, $element->nodeType);
                $this->assertSame($doc, $element->ownerDocument, 'ownerDocument');
                $qualified = (string) $qualifiedName;

                if (mb_strpos($qualified, ':') !== false) {
                    $names = explode(':', $qualified, 2);
                } else {
                    $names = [null, $qualified];
                }

                $this->assertSame($names[0], $element->prefix, 'prefix');
                $this->assertSame($names[1], $element->localName, 'localName');
                $this->assertSame($namespace, $element->namespaceURI, 'namespaceURI');
            }

            if (!$doctype) {
                $this->assertNull($doc->doctype, 'doctype');
            } else {
                $this->assertSame($doctype, $doc->doctype);
                $this->assertSame($doc, $doc->doctype->ownerDocument);
            }

            if ($omitRootElement && $doctype || !$omitRootElement && !$doctype) {
                $count = 1;
            } elseif ($omitRootElement && !$doctype) {
                $count = 0;
            } else {
                $count = 2;
            }

            $this->assertSame($count, $doc->childNodes->length);
        }
    }

    /**
     * @dataProvider noErrorProvider
     */
    public function testCreateDocumentMetadata(
        ?string $namespace,
        ?string $qualifiedName,
        ?DocumentType $doctype
    ): void {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createDocument(
            $namespace,
            $qualifiedName,
            $doctype
        );
        // TODO: Should we support the compatMode attribute?
        // $this->assertSame('CSS1Compat', $doc->compatMode);
        $this->assertSame('UTF-8', $doc->characterSet);

        if ($namespace === Namespaces::HTML) {
            $expectedNamespace = 'application/xhtml+xml';
        } elseif ($namespace === Namespaces::SVG) {
            $expectedNamespace = 'image/svg+xml';
        } else {
            $expectedNamespace = 'application/xml';
        }

        $this->assertSame($expectedNamespace, $doc->contentType);
        $this->assertSame('about:blank', $doc->URL);
        $this->assertSame('about:blank', $doc->documentURI);
        $this->assertSame('DIV', $doc->createElement('DIV')->localName);
    }

    /**
     * @dataProvider noErrorProvider
     */
    public function testCharacterSetAliases(?string $namespace, ?string $qualifiedName, ?DocumentType $doctype): void
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createDocument($namespace, $qualifiedName, $doctype);
        $this->assertSame('UTF-8', $doc->characterSet, 'characterSet');
        // TODO: Should we support these aliases?
        //$this->assertSame('UTF-8', $doc->charset, 'charset');
        //$this->assertSame('UTF-8', $doc->inputEncoding, 'inputEncoding');
    }

    public function testCreateDocumentWithMissingArgsShouldThrow(): void
    {
        $document = $this->getHTMLDocument();
        $this->assertThrows(static function () use ($document): void {
            $document->implementation->createDocument();
        }, TypeError::class);
        $this->assertThrows(static function () use ($document): void {
            $document->implementation->createDocument('');
        }, TypeError::class);
    }

    public function getTestData(): array
    {
        if (!$this->tests) {
            $document = $this->getHTMLDocument();

            $this->tests = array_merge(
                array_map(function ($t) {
                    return [$t[0], $t[1], null, $t[2]];
                }, $this->getCreateElementNSTests()),
                [
                    /* Arrays with four elements:
                     *   the namespace argument
                     *   the qualifiedName argument
                     *   the doctype argument
                     *   the expected exception, or null if none
                     */
                    [null, null, false, TypeError::class],
                    [null, "", null, null],
                    ["http://example.com/", null, null, null],
                    ["http://example.com/", "", null, null],
                    ["/", null, null, null],
                    ["/", "", null, null],
                    ["http://www.w3.org/XML/1998/namespace", null, null, null],
                    ["http://www.w3.org/XML/1998/namespace", "", null, null],
                    ["http://www.w3.org/2000/xmlns/", null, null, null],
                    ["http://www.w3.org/2000/xmlns/", "", null, null],
                    ["foo:", null, null, null],
                    ["foo:", "", null, null],
                    [null, null, $document->implementation->createDocumentType("foo", "", ""), null],
                    [null, null, $document->doctype, null], // This causes a horrible WebKit bug (now fixed in trunk).
                    [null, null, (function () use ($document) {
                        $foo = $document->implementation->createDocumentType("bar", "", "");
                        $document->implementation->createDocument(null, null, $foo);
                        return $foo;
                    })(), null], // DOCTYPE already associated with a document.
                    [null, null, (function () use ($document) {
                        $bar = $document->implementation->createDocument(null, null, null);
                        return $bar->implementation->createDocumentType("baz", "", "");
                    })(), null], // DOCTYPE created by a different implementation.
                    [null, null, (function () use ($document) {
                        $bar = $document->implementation->createDocument(null, null, null);
                        $magic = $bar->implementation->createDocumentType("quz", "", "");
                        $bar->implementation->createDocument(null, null, $magic);
                        return $magic;
                    })(), null], // DOCTYPE created by a different implementation and already associated with a document.
                    [null, "foo", $document->implementation->createDocumentType("foo", "", ""), null],
                    ["foo", null, $document->implementation->createDocumentType("foo", "", ""), null],
                    ["foo", "bar", $document->implementation->createDocumentType("foo", "", ""), null],
                    [Namespaces::HTML, "", null, null],
                    [Namespaces::SVG, "", null, null],
                    [Namespaces::MATHML, "", null, null],
                    [null, "html", null, null],
                    [null, "svg", null, null],
                    [null, "math", null, null],
                    [null, "", $document->implementation->createDocumentType(
                        "html",
                        "-//W3C//DTD XHTML 1.0 Transitional//EN",
                        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"
                    )],
                    [null, "", $document->implementation->createDocumentType(
                        "svg",
                        "-//W3C//DTD SVG 1.1//EN",
                        "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"
                    )],
                    [null, "", $document->implementation->createDocumentType(
                        "math",
                        "-//W3C//DTD MathML 2.0//EN",
                        "http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
                    )],
                ]
            );
        }

        return $this->tests;
    }

    public function noErrorProvider(): array
    {
        return array_filter($this->getTestData(), function ($value) {
            return !isset($value[3]) || $value[3] === null;
        });
    }
}
