<?php
namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Node;
use Rowbot\DOM\Tests\dom\Constants;
use Rowbot\DOM\Tests\dom\DocumentGetter;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/dom/nodes/Node-constants.html
 */
class NodeConstantsTest extends TestCase
{
    use Constants;
    use DocumentGetter;

    public function constantsProvider(): array
    {
        $document = $this->getHTMLDocument();

        return [
            [
                [
                    [Node::class, 'Node interface object'],
                    [$document->createElement('foo'), 'Element object'],
                    [$document->createTextNode('bar'), 'Text object']
                ],
                [
                    // "nodeType"
                    ["ELEMENT_NODE", 1],
                    ["ATTRIBUTE_NODE", 2],
                    ["TEXT_NODE", 3],
                    ["CDATA_SECTION_NODE", 4],
                    ["ENTITY_REFERENCE_NODE", 5],
                    ["ENTITY_NODE", 6],
                    ["PROCESSING_INSTRUCTION_NODE", 7],
                    ["COMMENT_NODE", 8],
                    ["DOCUMENT_NODE", 9],
                    ["DOCUMENT_TYPE_NODE", 10],
                    ["DOCUMENT_FRAGMENT_NODE", 11],
                    ["NOTATION_NODE", 12],

                    // "createDocumentPosition"
                    ["DOCUMENT_POSITION_DISCONNECTED", 0x01],
                    ["DOCUMENT_POSITION_PRECEDING", 0x02],
                    ["DOCUMENT_POSITION_FOLLOWING", 0x04],
                    ["DOCUMENT_POSITION_CONTAINS", 0x08],
                    ["DOCUMENT_POSITION_CONTAINED_BY", 0x10],
                    ["DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC", 0x20]
                ]
            ]
        ];
    }
}
