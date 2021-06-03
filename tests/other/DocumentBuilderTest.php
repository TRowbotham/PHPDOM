<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\other;

use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Exception\TypeError;
use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\XMLDocument;

class DocumentBuilderTest extends TestCase
{
    public function testNotSettingContentTypeThrows(): void
    {
        $this->expectException(TypeError::class);
        DocumentBuilder::create()->createEmptyDocument();
    }

    /**
     * @dataProvider validContentTypeStringProvider
     */
    public function testValidContentTypeStringDoesNotThrow(string $contentType): void
    {
        $document = null;

        try {
            $document = DocumentBuilder::create()->setContentType($contentType);
        } finally {
            self::assertInstanceOf(DocumentBuilder::class, $document);
        }
    }

    public function validContentTypeStringProvider(): Generator
    {
        $reflection = new ReflectionClass(DocumentBuilder::class);
        $reflection->getConstant('VALID_CONTENT_TYPES');

        foreach ($reflection->getConstant('VALID_CONTENT_TYPES') as $contentType) {
            yield [$contentType];
        }
    }

    public function testInvalidContentTypeThrows(): void
    {
        $this->expectException(TypeError::class);
        DocumentBuilder::create()->setContentType('image/png');
    }

    public function testInvalidURLThrows(): void
    {
        $this->expectException(TypeError::class);
        DocumentBuilder::create()->setDocumentUrl('//my.scheme-relative-url.com');
    }

    /**
     * @dataProvider contentTypeProvider
     */
    public function testBuilderCreatesCorrectDocumentBasedOnContentType(string $contentType, bool $isHtmlDocument): void
    {
        $builder = DocumentBuilder::create()->setContentType($contentType);
        $doc1 = $builder->createEmptyDocument()->isHTMLDocument();
        $doc2 = $builder->createFromString('Hello World!')->isHTMLDocument();
        $expectedDocNotOfType = $isHtmlDocument ? XMLDocument::class : HTMLDocument::class;

        self::assertSame($isHtmlDocument, $doc1);
        self::assertSame($isHtmlDocument, $doc2);
        self::assertNotInstanceOf($expectedDocNotOfType, $doc1);
        self::assertNotInstanceOf($expectedDocNotOfType, $doc2);
    }

    public function contentTypeProvider(): Generator
    {
        foreach ($this->validContentTypeStringProvider() as $contentType) {
            yield [$contentType[0], $contentType[0] === 'text/html'];
        }
    }
}
