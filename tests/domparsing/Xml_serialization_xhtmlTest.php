<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\domparsing;

use Rowbot\DOM\Document;
use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\TestCase;
use Rowbot\DOM\XMLSerializer;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/domparsing/xml-serialization.xhtml
 */
class Xml_serialization_xhtmlTest extends TestCase
{
    public function testCommentContainingDoubleDash(): void
    {
        $dt = $this->getDocument()->createComment('--');
        self::assertSame('<!------>', $this->serialize($dt));
    }

    public function testCommentStartingWithDash(): void
    {
        $dt = $this->getDocument()->createComment('- x');
        self::assertSame('<!--- x-->', $this->serialize($dt));
    }

    public function testCommentEndingWithDash(): void
    {
        $dt = $this->getDocument()->createComment('x -');
        self::assertSame('<!--x --->', $this->serialize($dt));
    }

    public function testCommentWithDoubleEnd(): void
    {
        $dt = $this->getDocument()->createComment('-->');
        self::assertSame('<!---->-->', $this->serialize($dt));
    }

    public function testDocumentTypeWithEmptyPublicAndSystemId(): void
    {
        $dt = $this->getDocument()->implementation->createDocumentType('html', '', '');
        self::assertSame('<!DOCTYPE html>', $this->serialize($dt));
    }

    public function testDocumentTypeWithEmptySystemId(): void
    {
        $dt = $this->getDocument()->implementation->createDocumentType('html', 'a', '');
        self::assertSame('<!DOCTYPE html PUBLIC "a">', $this->serialize($dt));
    }

    public function testDocumentTypeWithEmptyPublicId(): void
    {
        $dt = $this->getDocument()->implementation->createDocumentType('html', '', 'a');
        self::assertSame('<!DOCTYPE html SYSTEM "a">', $this->serialize($dt));
    }

    public function testDocumentTypeWithNonEmptyPublicAndSystemId(): void
    {
        $dt = $this->getDocument()->implementation->createDocumentType('html', 'a', 'b');
        self::assertSame('<!DOCTYPE html PUBLIC "a" "b">', $this->serialize($dt));
    }

    public function testDocumentTypeWithApostrophe(): void
    {
        $dt = $this->getDocument()->implementation->createDocumentType('html', '\'', '\'');
        self::assertSame("<!DOCTYPE html PUBLIC \"'\" \"'\">", $this->serialize($dt));
    }

    public function testDocumentTypeWithQuotationMark(): void
    {
        $dt = $this->getDocument()->implementation->createDocumentType('html', '"', '"');
        self::assertSame('<!DOCTYPE html PUBLIC """ """>', $this->serialize($dt));
    }

    public function testDocumentTypeWithApostropheAndQuotationMark(): void
    {
        $dt = $this->getDocument()->implementation->createDocumentType('html', '"\'', '\'"');
        self::assertSame('<!DOCTYPE html PUBLIC ""\'" "\'"">', $this->serialize($dt));
    }

    public function testElementHrefAttributesAreNotPercentEncoded(): void
    {
        $el = $this->getDocument()->createElement('a');
        $el->setAttribute("href", "\u{3042}\u{3044}\u{3046} !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~");
        self::assertSame("<a xmlns=\"http://www.w3.org/1999/xhtml\" href=\"\u{3042}\u{3044}\u{3046} !&quot;#$%&amp;'()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~\"></a>", $this->serialize($el));
    }

    public function testElementWithQueryPartsInHrefAttributesAreNotPercentEncoded(): void
    {
        $el = $this->getDocument()->createElement('a');
        $el->setAttribute("href", "?\u{3042}\u{3044}\u{3046} !\"$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~");
        self::assertSame("<a xmlns=\"http://www.w3.org/1999/xhtml\" href=\"?\u{3042}\u{3044}\u{3046} !&quot;$%&amp;'()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~\"></a>", $this->serialize($el));
    }

    public function testProcessingInstructionWithEmptyData(): void
    {
        $pi = $this->getDocument()->createProcessingInstruction('a', '');
        self::assertSame('<?a ?>', $this->serialize($pi));
    }

    public function testProcessingInstructionWithNonEmptyData(): void
    {
        $pi = $this->getDocument()->createProcessingInstruction('a', 'b');
        self::assertSame('<?a b?>', $this->serialize($pi));
    }

    public function testProcessingInstructionContainsXml(): void
    {
        $pi = $this->getDocument()->createProcessingInstruction('xml', 'b');
        self::assertSame('<?xml b?>', $this->serialize($pi));
    }

    public function testProcessingInstructionContainsColon(): void
    {
        $pi = $this->getDocument()->createProcessingInstruction('x:y', 'b');
        self::assertSame('<?x:y b?>', $this->serialize($pi));
    }

    private function getDocument(): Document
    {
        $document = new Document();
        $document->setContentType('application/xhtml+xml');

        return $document;
    }

    private function serialize(Node $node): string
    {
        $serializer = new XMLSerializer();

        return $serializer->serializeToString($node);
    }
}
