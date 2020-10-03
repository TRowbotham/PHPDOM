<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Document;
use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/DOMImplementation-createDocumentType.html
 */
class DOMImplementationCreateDocumentTypeTest extends TestCase
{
    use DocumentGetter;

    /**
     * @dataProvider getTestData
     */
    public function testCreateDocumentType(
        string $qualifiedName,
        string $publicId,
        string $systemId,
        ?string $expected
    ): void {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument('title');

        if ($expected) {
            $this->expectException($expected);
            $document->implementation->createDocumentType(
                $qualifiedName,
                $publicId,
                $systemId
            );
        } else {
            $this->doTest($document, $qualifiedName, $publicId, $systemId);
            $this->doTest($doc, $qualifiedName, $publicId, $systemId);
        }
    }

    public function doTest(
        Document $document,
        string $qualifiedName,
        string $publicId,
        string $systemId
    ): void {
        $doctype = $document->implementation->createDocumentType(
            $qualifiedName,
            $publicId,
            $systemId
        );
        $this->assertSame($qualifiedName, $doctype->name);
        $this->assertSame($qualifiedName, $doctype->nodeName);
        $this->assertSame($publicId, $doctype->publicId);
        $this->assertSame($systemId, $doctype->systemId);
        $this->assertSame($document, $doctype->ownerDocument);
        $this->assertNull($doctype->nodeValue);
    }

    public function getTestData(): array
    {
        return [
            ["", "", "", InvalidCharacterError::class],
            ["test:root", "1234", "", null],
            ["test:root", "1234", "test", null],
            ["test:root", "test", "", null],
            ["test:root", "test", "test", null],
            ["_:_", "", "", null],
            ["_:h0", "", "", null],
            ["_:test", "", "", null],
            ["_:_.", "", "", null],
            ["_:a-", "", "", null],
            ["l_:_", "", "", null],
            ["ns:_0", "", "", null],
            ["ns:a0", "", "", null],
            ["ns0:test", "", "", null],
            ["ns:EEE.", "", "", null],
            ["ns:_-", "", "", null],
            ["a.b:c", "", "", null],
            ["a-b:c.j", "", "", null],
            ["a-b:c", "", "", null],
            ["foo", "", "", null],
            ["1foo", "", "", InvalidCharacterError::class],
            ["foo1", "", "", null],
            ["f1oo", "", "", null],
            ["@foo", "", "", InvalidCharacterError::class],
            ["foo@", "", "", InvalidCharacterError::class],
            ["f@oo", "", "", InvalidCharacterError::class],
            ["edi:{", "", "", InvalidCharacterError::class],
            ["edi:}", "", "", InvalidCharacterError::class],
            ["edi:~", "", "", InvalidCharacterError::class],
            ["edi:'", "", "", InvalidCharacterError::class],
            ["edi:!", "", "", InvalidCharacterError::class],
            ["edi:@", "", "", InvalidCharacterError::class],
            ["edi:#", "", "", InvalidCharacterError::class],
            ["edi:$", "", "", InvalidCharacterError::class],
            ["edi:%", "", "", InvalidCharacterError::class],
            ["edi:^", "", "", InvalidCharacterError::class],
            ["edi:&", "", "", InvalidCharacterError::class],
            ["edi:*", "", "", InvalidCharacterError::class],
            ["edi:(", "", "", InvalidCharacterError::class],
            ["edi:)", "", "", InvalidCharacterError::class],
            ["edi:+", "", "", InvalidCharacterError::class],
            ["edi:=", "", "", InvalidCharacterError::class],
            ["edi:[", "", "", InvalidCharacterError::class],
            ["edi:]", "", "", InvalidCharacterError::class],
            ["edi:\\", "", "", InvalidCharacterError::class],
            ["edi:/", "", "", InvalidCharacterError::class],
            ["edi:;", "", "", InvalidCharacterError::class],
            ["edi:`", "", "", InvalidCharacterError::class],
            ["edi:<", "", "", InvalidCharacterError::class],
            ["edi:>", "", "", InvalidCharacterError::class],
            ["edi:,", "", "", InvalidCharacterError::class],
            ["edi:a ", "", "", InvalidCharacterError::class],
            ["edi:\"", "", "", InvalidCharacterError::class],
            ["{", "", "", InvalidCharacterError::class],
            ["}", "", "", InvalidCharacterError::class],
            ["'", "", "", InvalidCharacterError::class],
            ["~", "", "", InvalidCharacterError::class],
            ["`", "", "", InvalidCharacterError::class],
            ["@", "", "", InvalidCharacterError::class],
            ["#", "", "", InvalidCharacterError::class],
            ["$", "", "", InvalidCharacterError::class],
            ["%", "", "", InvalidCharacterError::class],
            ["^", "", "", InvalidCharacterError::class],
            ["&", "", "", InvalidCharacterError::class],
            ["*", "", "", InvalidCharacterError::class],
            ["(", "", "", InvalidCharacterError::class],
            [")", "", "", InvalidCharacterError::class],
            ["f:oo", "", "", null],
            [":foo", "", "", InvalidCharacterError::class],
            ["foo:", "", "", InvalidCharacterError::class],
            ["prefix::local", "", "", InvalidCharacterError::class],
            ["foo", "foo", "", null],
            ["foo", "", "foo", null],
            ["foo", "f'oo", "", null],
            ["foo", "", "f'oo", null],
            ["foo", 'f"oo', "", null],
            ["foo", "", 'f"oo', null],
            ["foo", "f'o\"o", "", null],
            ["foo", "", "f'o\"o", null],
            ["foo", "foo>", "", null],
            ["foo", "", "foo>", null],
        ];
    }
}
