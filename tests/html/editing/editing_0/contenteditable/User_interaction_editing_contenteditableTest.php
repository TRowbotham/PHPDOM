<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\html\editing\editing_0\contenteditable;

use Rowbot\DOM\DocumentBuilder;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/html/editing/editing-0/contenteditable/user-interaction-editing-contenteditable.html
 */
class User_interaction_editing_contenteditableTest extends TestCase
{
    public function testNoContentEditableAttribute(): void
    {
        $testElement = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument()->createElement('testElement');

        self::assertSame('inherit', $testElement->contentEditable);
    }

    public function testEmptyContentEditableAttribute(): void
    {
        $testElement = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument()->createElement('testElement');
        $testElement->setAttribute('contentEditable', '');

        self::assertTrue($testElement->isContentEditable);
        self::assertSame('true', $testElement->contentEditable);
    }

    public function testSetContentEditableToTrue(): void
    {
        $testElement = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument()->createElement('testElement');
        $testElement->contentEditable = 'true';

        self::assertTrue($testElement->isContentEditable);
        self::assertSame('true', $testElement->contentEditable);
    }

    public function testSetContentEditableToFalse(): void
    {
        $testElement = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument()->createElement('testElement');
        $testElement->contentEditable = 'false';

        self::assertFalse($testElement->isContentEditable);
        self::assertSame('false', $testElement->contentEditable);
    }

    public function testSetContentEditableToInherit(): void
    {
        $testElement = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument()->createElement('testElement');
        $testElement->contentEditable = 'inherit';

        self::assertFalse($testElement->isContentEditable);
        self::assertSame('inherit', $testElement->contentEditable);
    }

    public function testSetParentContentEditableToTrue(): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $testElement = $document->createElement('testElement');
        $childElement = $document->createElement('childElement');
        $testElement->appendChild($childElement);
        $testElement->contentEditable = 'true';

        self::assertTrue($testElement->isContentEditable);
        self::assertSame('true', $testElement->contentEditable);

        self::assertTrue($childElement->isContentEditable);
        self::assertSame('inherit', $childElement->contentEditable);
    }

    public function testSetParentContentEditableToFalse(): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $testElement = $document->createElement('testElement');
        $childElement = $document->createElement('childElement');
        $testElement->appendChild($childElement);
        $testElement->contentEditable = 'false';

        self::assertFalse($testElement->isContentEditable);
        self::assertSame('false', $testElement->contentEditable);

        self::assertFalse($childElement->isContentEditable);
        self::assertSame('inherit', $childElement->contentEditable);
    }

    public function testDynamicallyChangingContentEditableValues(): void
    {
        $document = DocumentBuilder::create()->setContentType('text/html')->createEmptyDocument();
        $testElement = $document->createElement('testElement');

        self::assertSame('inherit', $testElement->contentEditable);
        $testElement->setAttribute('contentEditable', '');

        self::assertTrue($testElement->isContentEditable);
        self::assertSame('true', $testElement->contentEditable);

        $testElement->contentEditable = 'true';
        self::assertTrue($testElement->isContentEditable);
        self::assertSame('true', $testElement->contentEditable);

        $testElement->contentEditable = 'false';
        self::assertFalse($testElement->isContentEditable);
        self::assertSame('false', $testElement->contentEditable);

        $testElement->contentEditable = 'inherit';
        self::assertSame('inherit', $testElement->contentEditable);

        $childElement = $document->createElement('childElement');
        $testElement->appendChild($childElement);
        $testElement->contentEditable = 'true';

        self::assertTrue($testElement->isContentEditable);
        self::assertSame('true', $testElement->contentEditable);
        self::assertTrue($childElement->isContentEditable);
        self::assertSame('inherit', $childElement->contentEditable);

        $testElement->contentEditable = 'false';
        self::assertFalse($testElement->isContentEditable);
        self::assertSame('false', $testElement->contentEditable);
        self::assertFalse($childElement->isContentEditable);
        self::assertSame('inherit', $childElement->contentEditable);
    }
}
