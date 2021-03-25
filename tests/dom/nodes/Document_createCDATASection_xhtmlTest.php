<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\CDATASection;
use Rowbot\DOM\Document;
use Rowbot\DOM\Exception\InvalidCharacterError;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-createCDATASection-xhtml.xhtml
 */
class Document_createCDATASection_xhtmlTest extends CharacterDataTestCase
{
    public function testCreateCDATASection(): void
    {
        $this->checkDocumentCreateMethod(new Document(), 'createCDATASection', CDATASection::class, 4, '#cdata-section', '');
    }

    public function testCDATASectionThrows(): void
    {
        $document = new Document();
        $this->expectException(InvalidCharacterError::class);
        $document->createCDATASection(" ]" . "]>  ");
    }
}
