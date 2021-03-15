<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\HTMLDocument;
use Rowbot\DOM\XMLDocument;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Node-cloneNode-XMLDocument.html
 */
class Node_cloneNode_XMLDocumentTest extends NodeTestCase
{
    public function testCloneNode(): void
    {
        $document = new HTMLDocument();
        $doc = $document->implementation->createDocument('namespace', '');

        self::assertInstanceOf(XMLDocument::class, $doc);
        $clone = $doc->cloneNode(true);
        self::assertInstanceOf(XMLDocument::class, $clone);
    }
}
