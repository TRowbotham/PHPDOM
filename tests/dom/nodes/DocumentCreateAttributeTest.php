<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function getXMLDocument()
    {
        if (self::$xmlDocument === null) {
            self::$xmlDocument = self
                ::getHTMLDocument()
                ->implementation
                ->createDocument(null, null, null);
        }

        return self::$xmlDocument;
    }

    #[DataProvider('invalidNamesProvider')]
    public function testInvalidNameHTMLCreateAttribute(string $name): void
    {
        $this->expectException(InvalidCharacterError::class);
        self::getHTMLDocument()->createAttribute($name);
    }

    #[DataProvider('invalidNamesProvider')]
    public function testInvalidNameXMLCreateAttribute(string $name): void
    {
        $this->expectException(InvalidCharacterError::class);
        self::getXMLDocument()->createAttribute($name);
    }

    #[DataProvider('validNamesProvider')]
    public function testValidNamesHTMLCreateAttribute(string $name): void
    {
        $attr = self::getHTMLDocument()->createAttribute($name);
        $this->attr_is($attr, '', $name, null, null, $name);
    }

    #[DataProvider('validNamesProvider')]
    public function testValidNamesXMLCreateAttribute(string $name): void
    {
        $attr = self::getXMLDocument()->createAttribute($name);
        $this->attr_is($attr, '', $name, null, null, $name);
    }

    #[DataProvider('attrNameProvider')]
    public function testHTMLDocumentCreateAttribute($name): void
    {
        $document = self::getHTMLDocument();
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

    #[DataProvider('attrNameProvider')]
    public function testXMLDocumentCreateAttribtue($name): void
    {
        $document = self::getXMLDocument();
        $attribute = $document->createAttribute($name);
        $this->attr_is($attribute, '', $name, null, null, $name);
        $this->assertNull($attribute->ownerElement);
    }

    public static function attrNameProvider(): array
    {
        return [
            ['title'],
            ['TITLE'],
            // [null],
        ];
    }
}
