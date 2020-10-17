<?php

declare(strict_types=1);

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Tests\dom\DocumentGetter;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/CharacterData-remove.html
 */
class CharacterDataRemoveTest extends NodeTestCase
{
    use ChildNodeRemoveTrait;
    use DocumentGetter;

    public function childNodeRemoveNodesProvider(): iterable
    {
        $document = $this->getHTMLDocument();

        return [
            [$document, $document->createTextNode('text'), $document->createElement('div')],
            [$document, $document->createComment('comment'), $document->createElement('div')],
            [
                $document,
                $document->createProcessingInstruction('foo', 'bar'),
                $document->createElement('div')
            ],
        ];
    }
}
