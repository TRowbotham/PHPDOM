<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/DocumentType-remove.html
 */
class DocumentTypeRemoveTest extends NodeTestCase
{
    use ChildNodeRemoveTrait;
    use DocumentGetter;

    public function childNodeRemoveNodesProvider(): iterable
    {
        $document = $this->getHTMLDocument();
        $node = $document->implementation->createDocumentType('html', '', '');
        $parentNode = $document->implementation->createDocument(null, '', null);

        return [
            [$document, $node, $parentNode],
        ];
    }
}
