<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/Element-remove.html
 */
class ElementRemoveTest extends NodeTestCase
{
    use ChildNodeRemoveTrait;
    use DocumentGetter;

    public function childNodeRemoveNodesProvider(): iterable
    {
        $document = $this->getHTMLDocument();
        $node = $document->createElement('div');
        $parentNode = $document->createElement('div');

        return [
            [$document, $node, $parentNode],
        ];
    }
}
