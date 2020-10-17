<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DocumentType;
use Rowbot\DOM\Tests\dom\WindowTrait;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/DocumentType-literal.html
 */
class DocumentTypeLiteralTest extends NodeTestCase
{
    use WindowTrait;

    public function testDocumentType(): void
    {
        $doctype = self::getWindow()->document->firstChild;

        $this->assertInstanceOf(DocumentType::class, $doctype);
        $this->assertSame('html', $doctype->name);
        $this->assertSame('STAFF', $doctype->publicId);
        $this->assertSame('staffNS.dtd', $doctype->systemId);
    }

    public static function getDocumentName(): string
    {
        return 'DocumentType-literal.html';
    }
}
