<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Exception\InvalidCharacterError;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\Utils;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createAttribute.html
 */
class DocumentCreateAttributeTest extends TestCase
{
    use Attributes;
    use DocumentGetter;
    use Productions;

    public function getXMLDocument()
    {
        if (!$this->xmlDocument) {
            $this->xmlDocument = $this
                ->getHTMLDocument()
                ->implementation
                ->createDocument(null, null, null);
        }

        return $this->xmlDocument;
    }

    /**
     * @dataProvider invalidNamesProvider
     */
    public function testInvalidNameHTMLCreateAttribute(string $name): void
    {
        $this->expectException(InvalidCharacterError::class);
        $this->getHTMLDocument()->createAttribute($name);
    }

    /**
     * @dataProvider invalidNamesProvider
     */
    public function testInvalidNameXMLCreateAttribute(string $name): void
    {
        $this->expectException(InvalidCharacterError::class);
        $this->getXMLDocument()->createAttribute($name);
    }

    /**
     * @dataProvider validNamesProvider
     */
    public function testValidNamesHTMLCreateAttribute(string $name): void
    {
        $attr = $this->getHTMLDocument()->createAttribute($name);
        $this->attr_is($attr, '', $name, null, null, $name);
    }

    /**
     * @dataProvider validNamesProvider
     */
    public function testValidNamesXMLCreateAttribute(string $name): void
    {
        $attr = $this->getXMLDocument()->createAttribute($name);
        $this->attr_is($attr, '', $name, null, null, $name);
    }

    /**
     * @dataProvider attrNameProvider
     */
    public function testHTMLDocumentCreateAttribute($name): void
    {
        $document = $this->getHTMLDocument();
        $attribute = $document->createAttribute($name);
        $this->attr_is(
            $attribute,
            '',
            Utils::toASCIILowerCase($name),
            null,
            null,
            Utils::toASCIILowerCase($name)
        );
        $this->assertNull($attribute->ownerElement);
    }

    /**
     * @dataProvider attrNameProvider
     */
    public function testXMLDocumentCreateAttribtue($name): void
    {
        $document = $this->getXMLDocument();
        $attribute = $document->createAttribute($name);
        $this->attr_is($attribute, '', $name, null, null, $name);
        $this->assertNull($attribute->ownerElement);
    }

    public function attrNameProvider(): array
    {
        return [
            ['title'],
            ['TITLE'],
            // [null],
        ];
    }
}
