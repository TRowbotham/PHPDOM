<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\DOMImplementation;
use Rowbot\DOM\Tests\dom\DocumentGetter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Document-implementation.html
 */
class DocumentImplementationTest extends NodeTestCase
{
    use DocumentGetter;

    public function testGettingImplementationOffTheSameDocument(): void
    {
        $document = $this->getHTMLDocument();
        $implementation = $document->implementation;
        $this->assertInstanceOf(DOMImplementation::class, $implementation);
        $this->assertSame($implementation, $document->implementation);
    }

    public function testGettingImplementationOffDifferentDocuments(): void
    {
        $document = $this->getHTMLDocument();
        $doc = $document->implementation->createHTMLDocument();
        $this->assertNotSame($doc->implementation, $document->implementation);
    }
}
